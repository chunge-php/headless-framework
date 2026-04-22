<?php

namespace app\modules\myclass;

use DateInterval;
use DateTime;
use support\Model;

use SplFileObject;
use support\Db;

class TemplateImport
{
    private array $options = [
        // CSV 解析
        'delimiter'      => null,   // null=自动嗅探, 否则明确指定 ',' ';' "\t"
        'enclosure'      => '"',
        'escape'         => '\\',
        'chunk'          => 500,
        'skip_header'    => true,
        'table'          => null,

        // 扩展行为
        'id_generator'   => null,   // fn(array $mapped, int $seq): mixed
        'progress'       => null,   // fn(int $done, int $total, float $percent)
        'max_error_keep' => 200,
        'timestamp'      => false,
        'soft_trim'      => true,
        'skip_comment'   => true,   // 跳过以 '#' 开头的注释行
        'transcode_to_utf8' => true,            // 自动把非UTF-8的CSV转码为UTF-8
        'detect_encodings'  => ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ISO-8859-1', 'UTF-16LE', 'UTF-16BE'],
        // 落库方式：insertAll 或 upsert(若可用)
        // ['uniqueBy'=>['email'], 'update'=>['name','phone']]
        'upsert'         => null,
        'upsert_custom' => [
            // 用哪些字段来匹配“是否已存在”
            // 例：['uid','name','type']  →  WHERE uid=? AND name=? AND type=?
            'match_by' => null,   // 必填：数组或回调

            // 命中时允许被更新的字段（默认=除 match_by、id、created_at 以外的所有键）
            'update'   => null,   // 可选：['phone','email','updated_at'] 等

            // 可选：更新/插入前的行级变换钩子（按需使用）
            'on_update' => null,  // fn(array &$updateData, array $row): void
            'on_insert' => null,  // fn(array &$insertData, array $row): void
        ],
        // Header 映射与别名：键为“来源列名”(大小写不敏感)，值可为目标字段名或 [目标字段名, 别名数组]
        // 也可在 import() 传 $headerMap 覆盖
        'header_map'     => null,

        // 行级钩子
        'beforeMap'      => null, // fn(array $assoc, int $rowIndex): array
        'afterMap'       => null, // fn(array $mapped, int $rowIndex): array
        'validate'       => null, // fn(array $mapped, int $rowIndex): void (抛异常即失败)

        // 错误导出
        'error_report'   => [
            'enable'   => true,
            'filename' => null, // 自动生成
        ],
        'row_state' => false, //是否返回转换后数据

        // ↓↓↓ 临时文件/远程下载 ↓↓↓
        'tmp_dir'        => null,     // 默认 runtime_path('imports/tmp')
        'delete_tmp'     => true,
        'http'           => [
            'headers'          => [],
            'timeout'          => 30,
            'connect_timeout'  => 10,
            'verify_ssl'       => true,
            'max_bytes'        => 0,
            'follow_redirects' => true,
        ],
        'map_by_index' => false,      // 开启后，不看表头内容，按列下标映射
        'index_map'    => null,       // 关联式：如 [0=>'first_name', 1=>'last_name', 3=>'email']
        'index_fields' => null,       // 顺序式：如 ['first_name','last_name','phone','email'] 等价于 0..n-1
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_replace_recursive($this->options, $options);
        if (!$this->options['tmp_dir']) {
            $this->options['tmp_dir'] = function_exists('runtime_path')
                ? rtrim(runtime_path('imports/tmp'), '/\\')
                : sys_get_temp_dir();
        }
        if (!is_dir($this->options['tmp_dir'])) {
            @mkdir($this->options['tmp_dir'], 0777, true);
        }
    }
    private function maybeTranscodeFileToUtf8(string $path): string
    {
        $sample = file_get_contents($path, false, null, 0, 1024 * 128) ?: '';
        // 去掉 UTF-8 BOM
        if (str_starts_with($sample, "\xEF\xBB\xBF")) {
            return $path; // 已是 UTF-8 with BOM，按现有逻辑首行会去BOM
        }
        $enc = mb_detect_encoding($sample, $this->options['detect_encodings'], true);
        if (!$enc || strtoupper($enc) === 'UTF-8') {
            return $path; // 已是 UTF-8，无需转码
        }
        $raw = file_get_contents($path);
        if ($raw === false) return $path;
        $utf8 = mb_convert_encoding($raw, 'UTF-8', $enc);
        $tmp  = $this->options['tmp_dir'] . DIRECTORY_SEPARATOR . ('utf8_' . uniqid() . '.csv');
        file_put_contents($tmp, $utf8);
        return $tmp;
    }

    /**
     * $csvPathOrUrl 可为本地路径或 http/https 链接
     * $mapRow：可选行映射函数；若未提供则使用 $headerMap/$this->options['header_map'] 直接映射
     * $headerMap：['CSV列名' => 'db字段'] 或 ['CSV列名' => ['db字段', ['别名1','别名2']]]
     */
    public function import(string $csvPathOrUrl, ?callable $mapRow = null, ?array $headerMap = null): array
    {
        [$localPath, $needCleanup] = $this->ensureLocalPath($csvPathOrUrl);
        if ($this->options['transcode_to_utf8']) {
            $localPath = $this->maybeTranscodeFileToUtf8($localPath); // ← 新增
            // 注意：maybeTranscodeFileToUtf8 返回的新文件也属于临时文件
            $needCleanup = true || $needCleanup;
        }
        $errorReportPath = null;

        try {
            if (!is_file($localPath)) {
                throw new \RuntimeException("CSV 文件不存在：{$localPath}");
            }
            if (!$mapRow && !$this->options['table'] && !$headerMap && !$this->options['header_map']) {
                throw new \InvalidArgumentException('未提供 mapRow，且未指定 table/header_map');
            }

            $file = new SplFileObject($localPath);
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            // 分隔符自动嗅探（仅针对首行）
            $delimiter = $this->options['delimiter'] ?? $this->sniffDelimiter($localPath);
            $file->setCsvControl($delimiter, $this->options['enclosure'], $this->options['escape']);

            $total = 0;
            $ok = 0;
            $fail = 0;
            $errors = [];
            $errorRows = []; // 用于错误导出
            $batch  = [];
            $header = null;
            $headerKeyMap = null; // 标准化之后的匹配 Key（大小写不敏感）

            $rowIndex = -1; // 从文件第0行开始计，用于报错时 +1 显示实际行号
            foreach ($file as $row) {
                $rowIndex++;
                if ($row === [null] || $row === false) {
                    continue;
                }

                // 处理 BOM
                if ($rowIndex === 0 && isset($row[0])) {
                    $row[0] = ltrim((string)$row[0], "\xEF\xBB\xBF");
                }

                // 跳过注释行
                if ($this->options['skip_comment'] && isset($row[0]) && is_string($row[0]) && str_starts_with(ltrim($row[0]), '#')) {
                    continue;
                }

                // Header 解析
                if ($rowIndex === 0 && ($this->options['skip_header'] ?? true)) {
                    $header = $this->normalizeHeaderRow($row);
                    // 构造大小写不敏感的匹配表
                    $headerKeyMap = array_flip($header);
                    continue;
                }

                // 原始行 → 关联数组
                // $assoc = $header ? $this->combineHeaderAssoc($header, $row) : $row;
                // 关键：如果开启 map_by_index，就直接按下标拿值，不依赖 header 文本
                if ($this->options['map_by_index']) {
                    // 默认第一行仍作为标题行跳过（skip_header=true），如果你想第一行就是数据，改成 false
                    $assoc = $this->rowToIndexAssoc($row);
                } else {
                    // 原逻辑：有表头则按表头键名组合，否则就是纯数组
                    $assoc = $header ? $this->combineHeaderAssoc($header, $row) : $row;
                }
                // 软 trim
                if ($this->options['soft_trim']) {
                    array_walk_recursive($assoc, function (&$v) {
                        if (is_string($v)) $v = trim($v);
                    });
                }

                // beforeMap 钩子（可清洗原始）
                if (is_callable($this->options['beforeMap'])) {
                    $assoc = call_user_func($this->options['beforeMap'], $assoc, $rowIndex + 1);
                }

                // 行计数（只统计数据行）
                $total++;

                try {
                    // 若未提供 mapRow，则使用 headerMap / options.header_map
                    $effectiveHeaderMap = $headerMap ?? $this->options['header_map'] ?? [];
                    if (!$mapRow && !empty($effectiveHeaderMap)) {
                        $mapRow = function (array $r) use ($effectiveHeaderMap) {
                            $data = [];
                            foreach ($effectiveHeaderMap as $csvKey => $dbDef) {
                                // 支持 ['db_key', ['别名1','别名2']]
                                $dbKey = is_array($dbDef) ? $dbDef[0] : $dbDef;
                                $aliases = is_array($dbDef) && isset($dbDef[1]) ? (array)$dbDef[1] : [];
                                $val = $this->getValueByAliases($r, $csvKey, $aliases);
                                $data[$dbKey] = $val;
                            }
                            return $data;
                        };
                    }

                    $mapped = $mapRow ? $mapRow($assoc) : $assoc;

                    if (!is_array($mapped) || empty($mapped)) {
                        throw new \RuntimeException('映射结果为空');
                    }

                    // id 生成
                    if (is_callable($this->options['id_generator']) && !isset($mapped['id'])) {
                        $mapped['id'] = call_user_func($this->options['id_generator'], $mapped, $total);
                    }

                    // 时间戳
                    if ($this->options['timestamp']) {
                        $now = date('Y-m-d H:i:s');
                        $mapped['created_at'] = $mapped['created_at'] ?? $now;
                        $mapped['updated_at'] = $mapped['updated_at'] ?? $now;
                    }

                    // afterMap 钩子
                    if (is_callable($this->options['afterMap'])) {
                        $mapped = call_user_func($this->options['afterMap'], $mapped, $rowIndex + 1);
                    }

                    // validate 钩子
                    if (is_callable($this->options['validate'])) {
                        call_user_func($this->options['validate'], $mapped, $rowIndex + 1);
                    }

                    $batch[] = $mapped;

                    if (count($batch) >= (int)$this->options['chunk']) {
                        $this->flush($batch);
                        $ok += count($batch);
                        $batch = [];
                        $this->tickProgress($ok, $total);
                    }
                } catch (\Throwable $e) {
                    $fail++;
                    $errRow = $header ? $assoc : ['__raw__' => $row];
                    $errMsg = $e->getMessage();
                    if (count($errors) < (int)$this->options['max_error_keep']) {
                        $errors[] = ['line' => $rowIndex + 1, 'error' => $errMsg];
                    }
                    if ($this->options['error_report']['enable']) {
                        $errRow['__error__'] = $errMsg;
                        $errorRows[] = $errRow;
                    }
                }
            }
            if ($this->options['row_state']) {
                return $batch;
            }
            if ($batch) {
                $this->flush($batch);
                $ok += count($batch);
                $this->tickProgress($ok, $total);
            }

            // 生成错误报告
            if ($this->options['error_report']['enable'] && !empty($errorRows)) {
                $errorReportPath = $this->writeErrorCsv($errorRows, $header);
            }

            return [
                'total'      => $total,
                'ok'         => $ok,
                'fail'       => $fail,
                'errors'     => $errors,
                'error_file' => $errorReportPath, // 可供下载
            ];
        } finally {
            if ($needCleanup && $this->options['delete_tmp']) {
                @unlink($localPath);
            }
        }
    }
    private function rowToIndexAssoc(array $row): array
    {
        // index_map 优先（稀疏/乱序）
        if (is_array($this->options['index_map']) && !empty($this->options['index_map'])) {
            $out = [];
            foreach ($this->options['index_map'] as $idx => $field) {
                $out[(string)$field] = $row[(int)$idx] ?? null;
            }
            return $out;
        }

        // 其次 index_fields（顺序：0..n-1）
        if (is_array($this->options['index_fields']) && !empty($this->options['index_fields'])) {
            $out = [];
            foreach (array_values($this->options['index_fields']) as $i => $field) {
                $out[(string)$field] = $row[$i] ?? null;
            }
            return $out;
        }

        // 如果两者都没给，就把每一列映射为 idx_0, idx_1 ... 供上层 mapRow 再处理
        $out = [];
        foreach ($row as $i => $val) {
            $out['idx_' . (int)$i] = $val;
        }
        return $out;
    }
    /** 支持：表名字符串 / 模型实例 / 模型类名，返回 Query Builder */
    private function resolveBuilder()
    {
        $t = $this->options['table'];

        // 1) 模型实例
        if ($t instanceof Model) {
            return $t->getConnection()->table($t->getTable());
        }

        // 2) 模型类名字符串
        if (is_string($t) && class_exists($t) && is_subclass_of($t, Model::class)) {
            /** @var Model $m */
            $m = new $t;
            return $m->getConnection()->table($m->getTable());
        }

        // 3) 纯表名字符串（走 support\Db）
        if (is_string($t)) {
            return Db::table($t);
        }

        throw new \InvalidArgumentException('options.table 必须是 表名字符串 / 模型实例 / 模型类名');
    }

    /** 返回用于事务的 Connection 对象（尽量与 Builder 同源） */
    private function resolveConnection()
    {
        $t = $this->options['table'];

        if ($t instanceof Model) {
            return $t->getConnection();
        }

        if (is_string($t) && class_exists($t) && is_subclass_of($t, Model::class)) {
            /** @var Model $m */
            $m = new $t;
            return $m->getConnection();
        }

        // 退回全局 Db 连接（webman 的 support\Db）
        return Db::connection();
    }

    private function flush(array $rows): void
    {
        if (!$this->options['table'] || empty($rows)) return;

        $builder    = $this->resolveBuilder();
        $connection = $this->resolveConnection();
        $table      = $this->options['table'];

        $upsert = $this->options['upsert'] ?? null; // 期望含 uniqueBy/update

        // —— 基于 uniqueBy 的逐行 UPDATE → INSERT —— //
        if ($upsert && !empty($upsert['uniqueBy'])) {
            $uniqueBy = (array)($upsert['uniqueBy'] ?? []);
            $updCols  = isset($upsert['update']) ? (array)$upsert['update'] : null;
            $now      = date('Y-m-d H:i:s');

            $connection->beginTransaction();
            try {
                $written = [];

                foreach ($rows as $r) {
                    // 1) 生成 where 条件：['col' => $r['col']]
                    $where = [];
                    foreach ($uniqueBy as $col) {
                        // 允许为空则用 null，对应 SQL: where col is null；若不允许可在此校验抛错
                        $where[$col] = $r[$col] ?? null;
                    }

                    // 2) 生成更新数据：只更新 $updCols，若未提供则默认=所有列 - uniqueBy - id - created_at
                    if ($updCols === null) {
                        $ban     = array_merge($uniqueBy, ['id', 'created_at']);
                        $updCols = array_values(array_diff(array_keys($r), $ban));
                    }
                    $updateData = [];
                    foreach ($updCols as $c) {
                        if (array_key_exists($c, $r)) $updateData[$c] = $r[$c];
                    }
                    if (!array_key_exists('updated_at', $updateData)) {
                        $updateData['updated_at'] = $now;
                    }

                    // 3) 先 UPDATE
                    $qb = $this->resolveBuilder(); // 每次拿新 builder，避免 where 污染
                    foreach ($where as $k => $v) {
                        // Laravel: where(null) 不会生效；手动处理 null
                        $v === null ? $qb->whereNull($k) : $qb->where($k, $v);
                    }
                    $affected = !empty($updateData) ? $qb->update($updateData) : 0;
                    if ($affected === 0) {
                        if (is_callable($this->options['id_generator']) && !isset($r['id'])) {
                            $r['id'] = call_user_func($this->options['id_generator'], $r, 0);
                            $this->resolveBuilder()->insert($r);
                        } else {
                            // 自增主键时可直接拿回 id
                            if (!isset($r['id'])) {
                                $r['id'] = $this->resolveBuilder()->insertGetId($r);
                            } else {
                                $this->resolveBuilder()->insert($r);
                            }
                        }
                    } else {
                        // UPDATE 成功但如果后续需要 id（例如绑定标签），可查回一次
                        if (($this->options['tag_binding']['enable'] ?? false) && empty($r['id'])) {
                            $q2 = $this->resolveBuilder()->select('id');
                            foreach ($where as $k => $v) {
                                $v === null ? $q2->whereNull($k) : $q2->where($k, $v);
                            }
                            $found = $q2->first();
                            if ($found && isset($found->id)) $r['id'] = (int)$found->id;
                        }
                    }

                    $written[] = $r;
                }

                // 若你有标签绑定逻辑，放这里（确保每条有 id/uid）：
                // $this->bindTagsForRows($written);

                $connection->commit();
                return;
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        // —— 没配 upsert 或未提供 uniqueBy：按原始 insert 批量写入（带降级） —— //
        $connection->beginTransaction();
        try {
            $this->resolveBuilder()->insert($rows);
            // $this->bindTagsForRows($rows);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            foreach ($rows as $r) {
                $connection->beginTransaction();
                try {
                    $this->resolveBuilder()->insert($r);
                    // $this->bindTagsForRows([$r]);
                    $connection->commit();
                } catch (\Throwable $e2) {
                    $connection->rollBack();
                    throw $e2;
                }
            }
        }
    }



    private function combineHeaderAssoc(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $idx => $key) {
            $assoc[$key] = $row[$idx] ?? null;
        }
        return $assoc;
    }

    private function tickProgress(int $done, int $total): void
    {
        if (is_callable($this->options['progress'])) {
            $percent = $total > 0 ? round($done * 100 / $total, 2) : 100.00;
            try {
                call_user_func($this->options['progress'], $done, $total, $percent);
            } catch (\Throwable) {
            }
        }
    }

    /** 传入本地路径或 http/https URL，返回 [本地路径, 是否需要清理] */
    private function ensureLocalPath(string $src): array
    {
        if ($this->isHttpUrl($src)) {
            $path = $this->downloadRemote($src, pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            return [$path, true];
        } else {
            $src = public_path($src);
        }
        return [$src, false];
    }

    private function isHttpUrl(string $s): bool
    {
        return str_starts_with($s, 'http://') || str_starts_with($s, 'https://');
    }

    /** 流式下载到 tmp_dir；保留扩展名用于错误报告友好 */
    private function downloadRemote(string $url, ?string $ext = null): string
    {
        $ext = $ext ?: 'csv';
        $tmp = $this->options['tmp_dir'] . DIRECTORY_SEPARATOR . ('csv_' . uniqid() . '.' . $ext);

        if (function_exists('curl_init')) {
            $fp = fopen($tmp, 'wb');
            if ($fp === false) {
                throw new \RuntimeException("无法创建临时文件：{$tmp}");
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE            => $fp,
                CURLOPT_FOLLOWLOCATION  => (bool)$this->options['http']['follow_redirects'],
                CURLOPT_CONNECTTIMEOUT  => (int)$this->options['http']['connect_timeout'],
                CURLOPT_TIMEOUT         => (int)$this->options['http']['timeout'],
                CURLOPT_SSL_VERIFYPEER  => (bool)$this->options['http']['verify_ssl'],
                CURLOPT_SSL_VERIFYHOST  => (bool)$this->options['http']['verify_ssl'] ? 2 : 0,
                CURLOPT_HTTPHEADER      => $this->formatHeaders($this->options['http']['headers']),
                CURLOPT_NOPROGRESS      => false,
                CURLOPT_PROGRESSFUNCTION => function ($resource, $dlTotal, $dlNow) {
                    $max = (int)$this->options['http']['max_bytes'];
                    if ($max > 0 && $dlNow > $max) {
                        return 1;
                    }
                    return 0;
                }
            ]);
            $ok = curl_exec($ch);
            $err = $ok ? null : curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            fclose($fp);

            if (!$ok || $code >= 400) {
                @unlink($tmp);
                throw new \RuntimeException("下载失败（HTTP {$code}）：{$err}");
            }
            return $tmp;
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => (int)$this->options['http']['timeout'],
                'header'  => implode("\r\n", $this->formatHeaders($this->options['http']['headers'])),
            ],
            'ssl'  => [
                'verify_peer'      => (bool)$this->options['http']['verify_ssl'],
                'verify_peer_name' => (bool)$this->options['http']['verify_ssl'],
            ],
        ]);

        $in = @fopen($url, 'rb', false, $ctx);
        if (!$in) throw new \RuntimeException("无法打开远程文件：{$url}");
        $out = @fopen($tmp, 'wb');
        if (!$out) {
            fclose($in);
            throw new \RuntimeException("无法创建临时文件：{$tmp}");
        }

        $max = (int)$this->options['http']['max_bytes'];
        $copied = 0;
        while (!feof($in)) {
            $buf = fread($in, 4 * 1024 * 1024);
            if ($buf === false) break;
            $copied += strlen($buf);
            if ($max > 0 && $copied > $max) {
                fclose($in);
                fclose($out);
                @unlink($tmp);
                throw new \RuntimeException("下载超出最大体积限制 {$max} bytes");
            }
            fwrite($out, $buf);
        }
        fclose($in);
        fclose($out);
        return $tmp;
    }

    private function formatHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $out[] = $v;
            } else {
                $out[] = $k . ': ' . $v;
            }
        }
        return $out;
    }

    /** Header 标准化：去两端空白、统一为小写、多个空格压缩为单空格 */
    private function normalizeHeaderRow(array $row): array
    {
        $norm = [];
        foreach ($row as $h) {
            $h = trim((string)$h);
            $h = preg_replace('/\s+/', ' ', $h);
            $norm[] = mb_strtolower($h);
        }
        return $norm;
    }

    /** 从 r 中按 主键+别名 获取值（大小写不敏感） */
    private function getValueByAliases(array $r, string $key, array $aliases = [])
    {
        $candidates = array_merge([$key], $aliases);
        // 统一 lower
        $lowerMap = [];
        foreach ($r as $k => $v) {
            $lowerMap[mb_strtolower(trim((string)$k))] = $v;
        }
        foreach ($candidates as $cand) {
            $cand = mb_strtolower(trim((string)$cand));
            if (array_key_exists($cand, $lowerMap)) {
                return $lowerMap[$cand];
            }
        }
        // 兼容原始键命中
        return $r[$key] ?? null;
    }

    /** 自动嗅探分隔符：从首行统计逗号/分号/制表符数量 */
    private function sniffDelimiter(string $path): string
    {
        $fh = fopen($path, 'rb');
        if (!$fh) return ',';
        $line = fgets($fh, 1024 * 1024);
        fclose($fh);
        if ($line === false) return ',';
        $line = ltrim($line, "\xEF\xBB\xBF");
        $c = substr_count($line, ',');
        $s = substr_count($line, ';');
        $t = substr_count($line, "\t");
        $max = max($c, $s, $t);
        if ($max === $t) return "\t";
        if ($max === $s) return ";";
        return ",";
    }

    /** 将错误行导出为 CSV，返回路径 */
    private function writeErrorCsv(array $rows, ?array $header): string
    {
        $fn = $this->options['error_report']['filename']
            ?: ($this->options['tmp_dir'] . DIRECTORY_SEPARATOR . 'import_errors_' . date('Ymd_His') . '.csv');

        $fp = fopen($fn, 'wb');
        if (!$fp) return '';

        // 写头
        if ($header) {
            $outHeader = array_merge($header, ['__error__']);
        } else {
            // 无头时仅输出所有键合集
            $keys = [];
            foreach ($rows as $r) {
                $keys = array_unique(array_merge($keys, array_keys($r)));
            }
            // 错误字段最后
            $keys = array_values(array_diff($keys, ['__error__']));
            $outHeader = array_merge($keys, ['__error__']);
        }

        fputcsv($fp, $outHeader);

        // 写行
        foreach ($rows as $r) {
            $line = [];
            foreach ($outHeader as $k) {
                $line[] = $r[$k] ?? '';
            }
            fputcsv($fp, $line);
        }
        fclose($fp);
        return $fn;
    }

    /* ===== 常用清洗工具（保留你的版本） ===== */
    public static function normalizeDate(?string $v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $ts = strtotime(str_replace('/', '-', $v));
        return $ts ? date('Y-m-d', $ts) : null;
    }
    public static function normalizeMoney($v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $v = preg_replace('/[^\d\.\-]/', '', $v);
        if (substr_count($v, '.') > 1) {
            $parts = explode('.', $v);
            $v = array_shift($parts) . '.' . implode('', $parts);
        }
        return $v === '' ? null : $v;
    }
    public static function normalizePhone($v): ?string
    {
        $v = preg_replace('/\D+/', '', (string)$v);
        return $v === '' ? null : $v;
    }
    public static function digitsOnly($v): ?string
    {
        $v = preg_replace('/\D+/', '', (string)$v);
        return $v === '' ? null : $v;
    }
}
