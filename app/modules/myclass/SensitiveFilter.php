<?php

namespace app\modules\myclass;

class SensitiveFilter
{
    protected  $trie = [];
    protected   $isBuilt = false;
    protected  $languages = ['zh', 'en', 'ja', 'ko'];
    // 词库目录（根据你项目修改）
    protected static  $dir  = BASE_PATH . '/resource/sensitive';

    // 是否排序（true = 按字母排序 / false = 不排序）
    protected static  $enableSort = true;
    /**
     * 直接调用
     * @param string $text    待检测文本
     * @param string $lang    zh|en|ja|ko|all
     * @return array 命中的敏感词（去重）
     */
    public function detect(string $text, string $lang = 'all'): array
    {
        $this->buildTrie($lang);
        return $this->searchText($text);
    }

    /**
     * 构建敏感词 Trie 树（只构建一次，提高性能）
     */
    protected function buildTrie(string $lang): void
    {
        if ($this->isBuilt) return;

        $files = $lang === 'all'
            ? $this->languages
            : [$lang];

        foreach ($files as $lg) {
            $file = base_path("resource/sensitive/{$lg}.txt");
            if (!is_file($file)) continue;

            $words = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($words as $word) {
                $this->insertTrie(mb_strtolower(trim($word)));
            }
        }

        $this->isBuilt = true;
    }
    /**
     * 清洗所有语言的 txt 文件
     */
    public static function cleanAll(): void
    {
        if (!is_dir(self::$dir)) {
            echo "[ERROR] Directory not found: " . self::$dir . PHP_EOL;
            return;
        }

        $files = glob(self::$dir . '/*.txt');
        if (empty($files)) {
            echo "[WARN] No txt files found in: " . self::$dir . PHP_EOL;
            return;
        }

        foreach ($files as $file) {
            self::cleanFile($file);
        }

        echo "[OK] All sensitive word files cleaned." . PHP_EOL;
    }

    /**
     * 单个文件清洗（去重 + 排序 + 覆盖写回）
     */
    public static function cleanFile(string $filePath): void
    {
        if (!is_file($filePath)) return;

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // 清洗：去空格、转小写
        $cleaned = array_map(fn($w) => trim(mb_strtolower($w)), $lines);
        $before  = count($cleaned);

        // 去重
        $cleaned = array_values(array_unique($cleaned));
        $after   = count($cleaned);

        // 排序（可关闭）
        if (self::$enableSort) {
            sort($cleaned, SORT_STRING);
        }

        // 覆盖写回
        $content = implode("\n", $cleaned) . "\n";
        file_put_contents($filePath, $content);

        echo "[OK] $filePath | Before: $before → After: $after" . PHP_EOL;
    }
    /**
     * 将一个词插入 trie 树
     */
    protected function insertTrie(string $word): void
    {
        $node = &$this->trie;
        $len = mb_strlen($word);

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($word, $i, 1);

            if (!isset($node[$char])) {
                $node[$char] = [];
            }
            $node = &$node[$char];
        }
        $node['_end'] = true; // 标记词结束
    }

    /**
     * 在文本中匹配敏感词
     */
    protected function searchText(string $text): array
    {
        $text    = mb_strtolower($text);
        $length  = mb_strlen($text);
        $hits    = [];

        for ($i = 0; $i < $length; $i++) {
            $node = $this->trie; // 每次从根开始
            $j = $i;

            while ($j < $length) {
                $char = mb_substr($text, $j, 1);

                if (!isset($node[$char])) {
                    break;
                }
                $node = $node[$char];

                if (isset($node['_end'])) {
                    $hits[] = mb_substr($text, $i, $j - $i + 1);
                }
                $j++;
            }
        }
        return array_values(array_unique($hits)); // 去重
    }
}
