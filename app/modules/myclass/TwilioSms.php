<?php

namespace app\modules\myclass;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
use support\Log;

trait DualCallable
{
    public static function __callStatic($name, $arguments)
    {
        return (new self())->$name(...$arguments);
    }
}

class TwilioSms
{
    use DualCallable;

    protected static $instance;
    protected $client;
    protected $from;
    protected $serviceSid;
    protected $error;

    // 可根据需要调整
    protected int $maxMediaCount = 10;    // Twilio 一次最多 10 个 mediaUrl
    protected int $maxTotalSizeHint = 5 * 1024 * 1024; // 业界常见上限 ~5MB（仅提示，不强制校验）
    protected string $baseUrl = '';
    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        try {
            $this->client = new Client(
                config('app.TWILIO_SID'),
                config('app.TWILIO_TOKEN')
            );
            $this->from = config('app.TWILIO_NUMBER');
            $this->serviceSid = config('app.TWILIO_ServiceSid');
            $this->error = null;
            $this->baseUrl = $this->detectBaseUrl();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error("Twilio client initialization failed: " . $this->error);
        }
    }

    /**
     * 发送短信（已存在）
     */
    public function sendSms(string $to, string $message, ?string $from = null): array
    {
        if (!$this->client) {
            return $this->errorResponse("Twilio client not initialized");
        }
        if (config('app.debugs')) return $this->successResponse('', '');

        try {
            $to = $this->formatPhoneNumber($to);
            $payload = [
                'body' => $message,
                'messagingServiceSid'=>$this->serviceSid,
            ];
            if (!empty($from ?? null)) {
                // 显式指定 from（必须是你的 Twilio 号码）
                $payload['from'] = $from;
            }
            $sms = $this->client->messages->create(
                $to,
                $payload
            );
            $messageStatus = $this->client->messages($sms->sid)->fetch()->status;
            // Log::debug("SMS sent successfully", [
            //     'to' => $to,
            //     'message_id' => $sms->sid,
            //     'status' => $messageStatus,
            // ]);

            return $this->successResponse($sms->sid, $messageStatus);
        } catch (RestException $e) {
            $errorMsg = $e->getMessage();
            $this->error = $errorMsg;
            debugMessage([
                'to' => $to,
                'error' => $errorMsg
            ], '短信发送失败');

            return $this->errorResponse($errorMsg);
        }
    }

    /**
     * 【新增】发送彩信（MMS）
     * @param string              $to      目标号码（E.164 或本地，内部会格式化）
     * @param string|null         $message 文本内容（可为空，仅发媒体也行）
     * @param string|array        $media   单个 URL 或 URL 数组（http/https）
     * @param array               $options 可选：['statusCallback' => 'https://...', 'from' => '...', 'messagingServiceSid' => '...']
     * @return array
     */
    public function sendMms(string $to, ?string $message, string|array $media, array $options = []): array
    {
        if (!$this->client) {
            return $this->errorResponse("Twilio client not initialized");
        }
        if (config('app.debugs')) return $this->successResponse('', '');

        try {
            $to = $this->formatPhoneNumber($to);

            // 规范化 mediaUrl 数组
            $mediaUrls = $this->normalizeMediaUrls($media, $options['baseUrl'] ?? null);
            if (empty($mediaUrls)) {
                return $this->errorResponse('mediaUrl is empty or invalid');
            }
            if (count($mediaUrls) > $this->maxMediaCount) {
                $mediaUrls = array_slice($mediaUrls, 0, $this->maxMediaCount);
            }

            // 组装发送参数
            $payload = [
                'mediaUrl' => $mediaUrls,
                'messagingServiceSid'=>$this->serviceSid,
            ];
            if (!empty($message)) {
                $payload['body'] = $message;
            }
            // 优先使用传入的 messagingServiceSid，其次使用默认 serviceSid，再次使用 from
            if (!empty($options['from'] ?? null)) {
                $payload['from'] = $options['from'];
            }
            if (!empty($options['statusCallback'] ?? null)) {
                $payload['statusCallback'] = $options['statusCallback'];
            }
            $msg = $this->client->messages->create($to, $payload);
            // 拉一次最新状态
            $status = $this->client->messages($msg->sid)->fetch()->status;
            Log::debug("MMS sent successfully", [
                'to' => $to,
                'message_id' => $msg->sid,
                'status' => $status,
                'media_count' => count($mediaUrls),
            ]);

            return $this->successResponse($msg->sid, $status);
        } catch (RestException $e) {
            $this->error = $e->getMessage();
            debugMessage([
                'to' => $to,
                'error' => $this->error
            ], '彩信发送失败');
            return $this->errorResponse($this->error);
        }
    }
    /**
     * 检测/推断基础域名（用于拼接相对路径）
     * 优先级：config('app.APP_URL') | config('app.base_url') > HTTP 头部推断 > 空串
     */
    protected function detectBaseUrl(): string
    {
        // 1) 从配置拿（推荐在 .env 里设置 APP_URL=https://example.com）
        $candidates = [
            config('app.domain_name_url'),
        ];
        foreach ($candidates as $c) {
            $c = (string)$c;
            if ($c !== '' && filter_var($c, FILTER_VALIDATE_URL)) {
                return rtrim($c, '/');
            }
        }

        // 2) 根据当前请求环境推断（仅在有 Web 请求时可用）
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if ($host !== '') {
            $url = $scheme . '://' . $host;
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return rtrim($url, '/');
            }
        }

        // 3) 回退为空（CLI 场景+未配置时），相对路径将无法补全
        return '';
    }

    /**
     * 规范化/校验 mediaUrl 列表：
     * - 接受 string 或 array
     * - 支持本地相对路径（/uploads/xx 或 uploads/xx），自动拼接域名
     * - 最终仅保留 http/https 绝对 URL
     */
    protected function normalizeMediaUrls(string|array $media, ?string $baseUrl = null): array
    {
        $urls = is_array($media) ? $media : [$media];
        $clean = [];
        $base = rtrim($baseUrl ?? $this->baseUrl, '/');

        foreach ($urls as $u) {
            $u = trim((string)$u);
            if ($u === '') continue;

            // 已经是 http(s) 绝对地址，直接保留
            if (preg_match('#^https?://#i', $u)) {
                if (filter_var($u, FILTER_VALIDATE_URL)) {
                    $clean[] = $u;
                }
                continue;
            }

            // 相对路径：/uploads/xxx 或 uploads/xxx
            if ($u[0] !== '/') {
                $u = '/' . $u; // 统一加一个前导斜杠
            }

            if ($base !== '') {
                $abs = $base . $u;
                if (filter_var($abs, FILTER_VALIDATE_URL)) {
                    $clean[] = $abs;
                }
            }
        }
        return $clean;
    }


    /**
     * （可保留你原来写法；若有需要，可进一步接入 libphonenumber 做更严格格式化）
     */
    private function formatPhoneNumber(string $phone): string
    {
        // 如果原始就以 + 开头，先保留
        $hasPlus = str_starts_with($phone, '+');

        // 去掉所有非数字字符（保留最前面的 +）
        $phone = $hasPlus ? ('+' . preg_replace('/\D/', '', ltrim($phone, '+'))) : preg_replace('/\D/', '', $phone);

        // 若没有 +，这里可按你的业务默认国家码（例如美国 1）
        if (!str_starts_with($phone, '+')) {
            $defaultCountryCode = '1'; // 如需别的国家可改
            $phone = '+' . $defaultCountryCode . $phone;
        }

        return $phone;
    }

    protected function successResponse(string $messageId, string $status): array
    {
        return [
            'success'    => true,
            'message_id' => $messageId,
            'status'     => $status,
            'error'      => null
        ];
    }

    protected function errorResponse(string $error): array
    {
        return [
            'success'    => false,
            'message_id' => null,
            'status'     => null,
            'error'      => $error
        ];
    }

    public function getLastError(): ?string
    {
        return $this->error;
    }
}
