<?php

namespace app\modules\myclass;

use support\Response;

class CsvTemplate
{
    /** @var array<string> */
    protected array $headers = [];
    /** @var array<string> 注释行（以 # 开头写入） */
    protected array $notes = [];
    /** @var array<int, array<string>> 多行示例 */
    protected array $examples = [];

    protected string $filename = '';
    protected bool $useBom = true;
    protected string $delimiter = ',';   // 分隔符
    protected string $enclosure = '"';   // 包裹符
    protected string $escapeChar = '\\'; // 转义符
    protected string $eol = "\r\n";      // 换行符，Excel 友好

    /** 工厂 */
    public static function make(): self
    {
        return new self();
    }
    /**
     * 下载预设模板
     * @param mixed $preset
     * @return Response
     */
    public function call($preset)
    {
        return  self::fromPreset($preset)->toDownloadResponse();
    }
    /**
     * 生成自定义模板内容
     * @param mixed $headers
     * @param mixed $filename
     * @param mixed $title
     * @param mixed $example
     * @param mixed $bom
     * @return Response
     */
    public function custom($headers, $filename, $title = '', $example = '', $bom = 1)
    {
        // Ad-hoc 动态生成（可通过 query 传 headers、notes、examples）
        $headers = $headers ? array_map('trim', explode(',', $headers)) : ['Col A', 'Col B', 'Col C'];
        $tpl = CsvTemplate::make()
            ->headers($headers)
            ->addNote((string)$title)  // 当作注释行
            ->bom($bom == 1 ? 1 : 0);

        // 单行示例
        if ($ex = (string) $example) {
            $tpl->addExample(array_map('trim', explode(',', $ex)));
        }
        $filename = (string) $filename;
        return $tpl->toDownloadResponse($filename ?: null);
    }
    /** 通过预设模板 id 载入（来自 config/csv_templates.php） */
    public static function fromPreset(string $id): self
    {
        $cfg = config('csv_templates.' . $id);
        if (!$cfg) {
            throw new \InvalidArgumentException("CSV preset '{$id}' not found");
        }
        $self = new self();
        $self->headers($cfg['headers'] ?? []);
        if (!empty($cfg['notes'])) {
            foreach ((array) $cfg['notes'] as $n) {
                $self->addNote($n);
            }
        }
        if (!empty($cfg['examples'])) {
            foreach ((array) $cfg['examples'] as $row) {
                $self->addExample($row);
            }
        }
        if (!empty($cfg['filename'])) {
            $self->filename($cfg['filename']);
        }
        if (isset($cfg['bom'])) {
            $self->bom((bool) $cfg['bom']);
        }
        // 可选：自定义分隔符等
        if (!empty($cfg['delimiter'])) $self->delimiter($cfg['delimiter']);
        if (!empty($cfg['enclosure'])) $self->enclosure($cfg['enclosure']);
        if (!empty($cfg['escape']))    $self->escape($cfg['escape']);
        if (!empty($cfg['eol']))       $self->eol($cfg['eol']);
        return $self;
    }

    /** 设置表头 */
    public function headers(array $headers): self
    {
        $this->headers = array_map(static fn($v) => trim((string) $v), $headers);
        return $this;
    }

    /** 添加一行注释（会写成 # xxx） */
    public function addNote(string $note): self
    {
        $note = trim($note);
        if ($note !== '') {
            $this->notes[] = $note;
        }
        return $this;
    }

    /** 添加一行示例（与表头对齐，不足会补空，多了会截断） */
    public function addExample(array $row): self
    {
        $this->examples[] = array_map(static fn($v) => (string) $v, $row);
        return $this;
    }

    /** 设置文件名 */
    public function filename(string $filename): self
    {
        $this->filename = trim($filename);
        return $this;
    }

    /** 是否加 UTF-8 BOM（Excel 友好） */
    public function bom(bool $use): self
    {
        $this->useBom = $use;
        return $this;
    }

    /** 自定义分隔符/包裹/转义/换行符（可选） */
    public function delimiter(string $d): self
    {
        $this->delimiter = $d;
        return $this;
    }
    public function enclosure(string $e): self
    {
        $this->enclosure = $e;
        return $this;
    }
    public function escape(string $e): self
    {
        $this->escapeChar = $e;
        return $this;
    }
    public function eol(string $e): self
    {
        $this->eol = $e;
        return $this;
    }

    /** 生成 CSV 字符串 */
    public function toString(): string
    {
        if (empty($this->headers)) {
            throw new \RuntimeException('CSV headers cannot be empty.');
        }

        $fp = fopen('php://temp', 'r+');

        // 注释行（每行一个单元格，防止被当数据）
        foreach ($this->notes as $n) {
            $this->putcsv($fp, ['# ' . $n]);
        }

        // 表头
        $this->putcsv($fp, $this->headers);

        // 示例行
        foreach ($this->examples as $row) {
            $row = $this->alignRow($row, count($this->headers));
            $this->putcsv($fp, $row);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        // 将默认 \n 替换为自定义 EOL（fputcsv 使用 PHP_EOL）
        if ($this->eol !== PHP_EOL) {
            $csv = str_replace(PHP_EOL, $this->eol, $csv);
        }

        if ($this->useBom) {
            $csv = "\xEF\xBB\xBF" . $csv;
        }
        return $csv;
    }

    /** 直接返回下载响应 */
    public function toDownloadResponse(?string $filename = null): Response
    {
        $name = $filename ?: $this->filename ?: ('csv_template_' . date('Ymd') . '.csv');
        $body = $this->toString();
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . addslashes($name) . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ];
        return new Response(200, $headers, $body);
    }

    /** 内部：写一行 CSV（自定义分隔符/包裹/转义） */
    protected function putcsv($fp, array $fields): void
    {
        // fputcsv 的 delimiter/enclosure/escapeChar 可自定义
        fputcsv($fp, $fields, $this->delimiter, $this->enclosure, $this->escapeChar);
    }

    /** 内部：对齐一行长度 */
    protected function alignRow(array $row, int $len): array
    {
        if (count($row) < $len) {
            return array_pad($row, $len, '');
        }
        if (count($row) > $len) {
            return array_slice($row, 0, $len);
        }
        return $row;
    }
}
