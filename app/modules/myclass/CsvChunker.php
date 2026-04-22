<?php
namespace app\modules\myclass;

use SplFileObject;
use Generator;
use InvalidArgumentException;

class CsvChunker
{
    protected string $path;
    protected bool   $hasHeader = true;
    protected string $delimiter = ',';
    protected string $enclosure = '"';
    protected string $escape    = '\\';
    protected ?string $sourceEncoding = 'auto'; // auto | 具体编码

    protected array $header = [];
    protected bool $headerResolved = false;

    public function __construct(string $path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("CSV 不存在或不可读：{$path}");
        }
        $this->path = $path;
    }

    public function withHeader(bool $has = true): self { $this->hasHeader = $has; return $this; }
    public function delimiter(string $d): self { $this->delimiter = $d; return $this; }
    public function enclosure(string $e): self { $this->enclosure = $e; return $this; }
    public function escape(string $e): self { $this->escape = $e; return $this; }
    /** 指定源编码；默认 auto 自动识别。可传 'GBK'、'Big5' 等 */
    public function encoding(?string $enc = 'auto'): self { $this->sourceEncoding = $enc; return $this; }

    public function getHeader(): array
    {
        if (!$this->headerResolved) $this->resolveHeader();
        return $this->header;
    }

    /** 分块读取：yield 每块二维数组（关联数组，UTF-8） */
    public function chunks(int $chunkSize = 500): Generator
    {
        if ($chunkSize < 1) {
            throw new InvalidArgumentException('chunkSize 必须 >= 1');
        }
    
        $file = $this->open();
        $this->applyCsvControl($file);
    
        if (!$this->headerResolved) {
            $this->resolveHeader($file);
        }
    
        $skipFirst = $this->hasHeader === true;
        $headerCount = count($this->header);
        $chunk = [];
    
        foreach ($file as $row) {
    
            if (!is_array($row)) continue;
    
            // 跳过 header
            if ($skipFirst) {
                $skipFirst = false;
                continue;
            }
    
            // 标准化编码
            $row = $this->normalizeRow($row);
    
            // 全空行跳过（包含空格、null、""）
            if ($this->isAllEmpty($row)) continue;
    
            // 对齐列数（多余截断、缺少补齐）
            $row = $this->padToHeader($row, $headerCount);
            $row = array_slice($row, 0, $headerCount);
    
            // 构建关联数组（永不报错）
            $assoc = array_combine($this->header, $row);
    
            // trim 效率更高
            foreach ($assoc as $k => $v) {
                if (is_string($v)) {
                    $assoc[$k] = trim($v);
                }
            }
    
            $chunk[] = $assoc;
    
            if (count($chunk) >= $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }
    
        if (!empty($chunk)) {
            yield $chunk;
        }
    }
    

    // ---------- 内部 ----------
    protected function open(): SplFileObject
    {
        $f = new SplFileObject($this->path, 'r');
        $f->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
            | SplFileObject::READ_AHEAD
        );
        return $f;
    }
    protected function applyCsvControl(SplFileObject $f): void
    {
        $f->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
    }

    protected function resolveHeader(?SplFileObject $file = null): void
    {
        $file ??= $this->open();
        $this->applyCsvControl($file);
        $file->rewind();

        // 先探测编码（读前 64KB）
        if ($this->sourceEncoding === 'auto') {
            $this->sourceEncoding = $this->detectEncoding($this->path) ?? 'UTF-8';
        }

        $first = $file->fgetcsv();
        if ($first === false || $first === [null]) { // 空文件
            $this->header = [];
            $this->headerResolved = true;
            return;
        }

        $first = $this->normalizeRow($first);
        if ($this->hasHeader) {
            // 去 BOM + 空标题补列名
            $first = $this->stripBomFromFirstField($first);
            foreach ($first as $i => $v) {
                $name = trim((string)($v ?? ''));
                $first[$i] = $name !== '' ? $name : "col_$i";
            }
            $this->header = $first;
        } else {
            $cols = count($first);
            $this->header = array_map(fn($i) => "col_$i", range(0, $cols - 1));
            $file->rewind(); // 第一行也是数据，回到开头
        }
        $this->headerResolved = true;
    }

    protected function normalizeRow(array $row): array
    {
        // 统一转为 UTF-8
        $src = strtoupper((string)$this->sourceEncoding);
        if ($src !== '' && $src !== 'UTF-8') {
            foreach ($row as $i => $v) {
                if ($v === null || $v === '') continue;
                $row[$i] = @iconv($src, 'UTF-8//IGNORE', (string)$v);
            }
        } else {
            // 纯 UTF-8：去掉潜在的 BOM
            $row = $this->stripBomFromFirstField($row);
        }
        return $row;
    }

    protected function stripBomFromFirstField(array $row): array
    {
        if (isset($row[0]) && is_string($row[0])) {
            // UTF-8 BOM
            if (str_starts_with($row[0], "\xEF\xBB\xBF")) {
                $row[0] = substr($row[0], 3);
            }
            // UTF-16/UTF-32 的 BOM 会在 detect + iconv 前处理；此处主要处理 UTF-8
        }
        return $row;
    }

    protected function isAllEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (is_string($v) && trim($v) !== '') return false;
            if ($v !== null && $v !== '') return false;
        }
        return true;
    }

    protected function padToHeader(array $row, int $cols): array
    {
        $n = count($row);
        if ($n < $cols) return array_pad($row, $cols, null);
        if ($n > $cols) return array_slice($row, 0, $cols);
        return $row;
    }

    /** 粗略探测编码并处理 BOM（返回如 UTF-8/GBK/Big5/Shift-JIS/UTF-16LE/UTF-16BE） */
    protected function detectEncoding(string $file): ?string
    {
        $fh = @fopen($file, 'rb');
        if (!$fh) return null;
        $sample = fread($fh, 65536) ?: '';
        fclose($fh);

        // BOM 直判
        $b2 = substr($sample, 0, 2);
        $b3 = substr($sample, 0, 3);
        $b4 = substr($sample, 0, 4);
        if ($b3 === "\xEF\xBB\xBF") return 'UTF-8';
        if ($b2 === "\xFF\xFE")     return 'UTF-16LE';
        if ($b2 === "\xFE\xFF")     return 'UTF-16BE';
        if ($b4 === "\xFF\xFE\x00\x00") return 'UTF-32LE';
        if ($b4 === "\x00\x00\xFE\xFF") return 'UTF-32BE';

        // mb_detect_encoding 尝试（不严格）
        if (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding(
                $sample,
                ['UTF-8','GBK','GB2312','BIG5','SHIFT-JIS','SJIS','EUC-JP','ISO-8859-1','CP1252'],
                false
            );
            if ($enc) return strtoupper($enc);
        }

        // UTF-8 合法性简单检查
        if ($this->seemsUtf8($sample)) return 'UTF-8';

        // 回退：常见国区 Excel 导出
        return 'GBK';
    }

    protected function seemsUtf8(string $str): bool
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) continue;
            elseif (($c & 0xE0) === 0xC0) $n = 1;
            elseif (($c & 0xF0) === 0xE0) $n = 2;
            elseif (($c & 0xF8) === 0xF0) $n = 3;
            else return false;
            while ($n-- && ++$i < $len) {
                if ((ord($str[$i]) & 0xC0) !== 0x80) return false;
            }
        }
        return true;
    }
}
