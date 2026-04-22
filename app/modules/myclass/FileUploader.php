<?php

namespace app\modules\myclass;

use Webman\Config;
use support\Cache;
// use OSS\OssClient;          // 阿里云OSS
// use Qcloud\Cos\Client as CosClient; // 腾讯云COS
// use Qiniu\Auth as QiniuAuth;
// use Qiniu\Storage\UploadManager;

class FileUploader
{
    protected $driver;
    protected $config;
    protected $chunkConfig = [
        'chunk_dir' => '/tmp/upload_chunks', // 分片临时目录
        'chunk_expire' => 86400, // 分片过期时间(秒)
    ];

    public function __construct(?string $driver = null)
    {
        $uploadConfig = Config::get('upload');
        $this->driver = $driver ?: ($uploadConfig['default'] ?? 'local');
        $this->config = $uploadConfig[$this->driver] ?? [];

        // 合并分片配置
        if (isset($uploadConfig['chunk'])) {
            $this->chunkConfig = array_merge($this->chunkConfig, $uploadConfig['chunk']);
        }

        // 确保分片目录存在
        if (!is_dir($this->chunkConfig['chunk_dir'])) {
            mkdir($this->chunkConfig['chunk_dir'], 0755, true);
        }
        // 阿里云断点续传 checkpoint 目录
        if (($this->driver === 'aliyun') && !empty($this->config['resumable'])) {
            $cp = $this->config['checkpoint_dir'] ?? (function_exists('runtime_path')
                ? runtime_path('uploads/oss_cp') : sys_get_temp_dir() . '/oss_cp');
            if (!is_dir($cp)) @mkdir($cp, 0755, true);
        }
    }
    /**
     * 上传分片文件
     */
    public function uploadChunk(
        string $chunkPath,
        string $filename,
        string $identifier,
        int $chunkNumber,
        int $totalChunks,
        int $chunkSize,
        int $totalSize
    ): array {
        // 验证分片文件
        if (!file_exists($chunkPath)) {
            throw new \Exception("分片文件不存在");
        }

        // 创建分片存储目录
        $chunkDir = $this->getChunkDir($identifier);
        if (!is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        // 移动分片到临时目录
        $chunkFile = $this->getChunkFilename($identifier, $chunkNumber);
        if (!rename($chunkPath, $chunkFile)) {
            throw new \Exception("无法保存分片文件");
        }

        // 记录分片上传状态
        $this->markChunkUploaded($identifier, $filename, $chunkNumber);

        return [
            'chunkNumber' => $chunkNumber,
            'totalChunks' => $totalChunks,
            'status' => 'uploaded',
        ];
    }

    /**
     * 检查分片上传状态
     */
    public function checkChunks(string $identifier, string $filename, int $totalChunks): array
    {
        $uploadedChunks = [];

        // 检查已上传的分片
        for ($i = 1; $i <= $totalChunks; $i++) {
            if ($this->isChunkUploaded($identifier, $filename, $i)) {
                $uploadedChunks[] = $i;
            }
        }

        return [
            'uploaded' => $uploadedChunks,
            'needed' => array_diff(range(1, $totalChunks), $uploadedChunks),
        ];
    }

    /**
     * 合并分片
     */
    public function mergeChunks(
        string $identifier,
        string $filename,
        int $totalChunks,
        int $totalSize
    ): array {
        // 验证所有分片是否已上传
        for ($i = 1; $i <= $totalChunks; $i++) {
            if (!$this->isChunkUploaded($identifier, $filename, $i)) {
                throw new \Exception("分片 {$i}/{$totalChunks} 未上传");
            }
        }

        // 创建临时合并文件
        $mergedFile = tempnam(sys_get_temp_dir(), 'merged_');
        $out = fopen($mergedFile, 'wb');

        if (!$out) {
            throw new \Exception("无法创建合并文件");
        }

        try {
            // 按顺序合并所有分片
            for ($i = 1; $i <= $totalChunks; $i++) {
                $chunkFile = $this->getChunkFilename($identifier, $i);
                $in = fopen($chunkFile, 'rb');

                if (!$in) {
                    throw new \Exception("无法读取分片 {$i}");
                }

                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }

                fclose($in);
                unlink($chunkFile); // 删除已合并的分片
            }

            fclose($out);

            // 验证文件大小
            if (filesize($mergedFile) !== $totalSize) {
                unlink($mergedFile);
                throw new \Exception("合并后文件大小不匹配");
            }

            // 上传合并后的文件
            $result = $this->upload($mergedFile, $filename);

            // 清理缓存记录
            $this->clearChunkStatus($identifier);

            return $result;
        } catch (\Exception $e) {
            if (is_resource($out)) fclose($out);
            if (file_exists($mergedFile)) unlink($mergedFile);
            throw $e;
        }
    }

    /**
     * 获取分片存储目录
     */
    protected function getChunkDir(string $identifier): string
    {
        return rtrim($this->chunkConfig['chunk_dir'], '/') . '/' . $identifier;
    }

    /**
     * 获取分片文件名
     */
    protected function getChunkFilename(string $identifier, int $chunkNumber): string
    {
        return $this->getChunkDir($identifier) . '/' . $chunkNumber;
    }

    /**
     * 标记分片已上传
     */
    protected function markChunkUploaded(string $identifier, string $filename, int $chunkNumber): void
    {
        $key = "upload:chunk:{$identifier}";
        $data = Cache::get($key, []);

        if (empty($data)) {
            $data = [
                'filename' => $filename,
                'chunks' => [],
                'created_at' => time(),
            ];
        }

        $data['chunks'][$chunkNumber] = true;
        Cache::set($key, $data, $this->chunkConfig['chunk_expire']);
    }

    /**
     * 检查分片是否已上传
     */
    protected function isChunkUploaded(string $identifier, string $filename, int $chunkNumber): bool
    {
        $key = "upload:chunk:{$identifier}";

        $data = Cache::get($key, []);

        // 验证文件名是否匹配
        if (empty($data) || $data['filename'] !== $filename) {
            return false;
        }

        return isset($data['chunks'][$chunkNumber]);
    }

    /**
     * 清理分片状态缓存
     */
    protected function clearChunkStatus(string $identifier): void
    {
        $key = "upload:chunk:{$identifier}";
        Cache::delete($key);

        // 删除分片目录
        $chunkDir = $this->getChunkDir($identifier);
        if (is_dir($chunkDir)) {
            array_map('unlink', glob("{$chunkDir}/*"));
            rmdir($chunkDir);
        }
    }
    /**
     * 动态切换驱动
     */
    public function setDriver(string $driver)
    {
        $uploadConfig = Config::get('upload');

        $this->driver = $driver;
        $this->config = $uploadConfig[$this->driver] ?? [];

        return $this; // 方便链式调用
    }

    /**
     * 上传文件
     *
     * @param string      $filePath  本地文件路径
     * @param string|null $fileName  目标文件名(含路径)，可选
     * @return array
     */
    public function upload(string $filePath, ?string $fileName = null): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("待上传文件不存在: {$filePath}");
        }

        switch ($this->driver) {
            case 'tencent':
                return $this->uploadToTencent($filePath, $fileName);

            case 'qiniu':
                return $this->uploadToQiniu($filePath, $fileName);

            case 'local':
            default:
                return $this->uploadToLocal($filePath, $fileName);
        }
    }

    /**
     * 上传到本地
     */
    public function uploadToLocal(string $filePath, ?string $fileName = null): array
    {
        $uploadPath = $this->config['upload_path'] ?? public_path() . '/uploads';
        $urlPrefix  = $this->config['url_prefix'] ?? '/uploads';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // === 生成文件名 ===
        if (!$fileName) {
            $subDir     = date('Ymd');
            $ext        = pathinfo($filePath, PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $fileName   = $subDir . '/' . $uniqueName;
        }

        $destination = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($fileName, '/\\');
        $subDirPath  = dirname($destination);
        if (!is_dir($subDirPath)) {
            mkdir($subDirPath, 0755, true);
        }

        // Windows 编码兼容
        if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
            $destination = iconv('UTF-8', 'GBK//IGNORE', $destination);
        }

        if (!@copy($filePath, $destination)) {
            throw new \Exception("文件保存到本地失败：" . $destination);
        }

        // 若是文本文件，确保 UTF-8 BOM
        $ext = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
        if (in_array($ext, ['csv', 'txt', 'json'])) {
            $content = file_get_contents($destination);
            $hasBom = strncmp($content, "\xEF\xBB\xBF", 3) === 0;
            if (!$hasBom) {
                $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'BIG5', 'ISO-8859-1'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }
                file_put_contents($destination, "\xEF\xBB\xBF" . $content);
            }
        }

        // ✅ 生成 URL 时只转义显示，不影响物理路径
        $encodedUrl = rtrim($urlPrefix, '/') . '/' .
            implode('/', array_map('rawurlencode', explode('/', ltrim($fileName, '/'))));

        return [
            'driver' => 'local',
            'path'   => $fileName,   // ✅ 真实路径（不要转义）
            'url'    => $encodedUrl, // ✅ 显示/访问用 URL（安全转义）
        ];
    }





    /**
     * 上传到腾讯云COS
     */
    protected function uploadToTencent(string $filePath, ?string $fileName = null): array
    {
        $secretId  = $this->config['secret_id'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $region    = $this->config['region'] ?? '';
        $bucket    = $this->config['bucket'] ?? '';
        $urlPrefix = $this->config['url_prefix'] ?? '';

        if (!$secretId || !$secretKey || !$region || !$bucket) {
            throw new \Exception("腾讯云COS配置不完整");
        }

        if (!$fileName) {
            $ext      = pathinfo($filePath, PATHINFO_EXTENSION);
            $fileName = date('Ymd/') . uniqid() . '.' . $ext;
        }

        try {
            $cosClient = new \Qcloud\Cos\Client([
                'region'      => $region,
                'schema'      => 'https',
                'credentials' => [
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey,
                ],
            ]);

            $cosClient->putObject([
                'Bucket' => $bucket,
                'Key'    => $fileName,
                'Body'   => fopen($filePath, 'rb')
            ]);
        } catch (\Exception $e) {
            throw new \Exception("上传到腾讯云COS失败: " . $e->getMessage());
        }

        $fileUrl = rtrim($urlPrefix, '/') . '/' . ltrim($fileName, '/');

        return [
            'driver' => 'tencent',
            'path'   => $fileName,
            'url'    => $fileUrl,
        ];
    }

    /**
     * 上传到七牛云
     */
    protected function uploadToQiniu(string $filePath, ?string $fileName = null): array
    {
        $accessKey = $this->config['access_key'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $bucket    = $this->config['bucket'] ?? '';
        $urlPrefix = $this->config['url_prefix'] ?? '';

        if (!$accessKey || !$secretKey || !$bucket) {
            throw new \Exception("七牛云配置不完整");
        }

        if (!$fileName) {
            $ext      = pathinfo($filePath, PATHINFO_EXTENSION);
            $fileName = date('Ymd/') . uniqid() . '.' . $ext;
        }

        try {
            $auth      = new \Qiniu\Auth($accessKey, $secretKey);
            $token     = $auth->uploadToken($bucket);
            $uploadMgr = new \Qiniu\Storage\UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $fileName, $filePath);

            if ($err !== null) {
                throw new \Exception($err->message());
            }
        } catch (\Exception $e) {
            throw new \Exception("上传到七牛云失败: " . $e->getMessage());
        }

        $fileUrl = rtrim($urlPrefix, '/') . '/' . ltrim($fileName, '/');

        return [
            'driver' => 'qiniu',
            'path'   => $fileName,
            'url'    => $fileUrl,
        ];
    }
}
