<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use support\Db;
use app\modules\myclass\ConsoleOutputStyles;

class GenerateFillable extends Command
{
    protected static $defaultName = 'table:key';

    protected function configure()
    {
        $this
            ->setDescription('扫描所有模型并为其生成/覆盖 $fillable')
            ->addOption('all', null, InputOption::VALUE_NONE, '扫描所有模型文件（app/modules/*/model 与 app/model）')
            ->addOption('dry', null, InputOption::VALUE_NONE, '仅预览，不写入文件');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ConsoleOutputStyles::initialize($output);

        $all = (bool)$input->getOption('all');
        $dry = (bool)$input->getOption('dry');

        if (!$all) {
            ConsoleOutputStyles::error($output, '请使用 --all 以扫描全部模型');
            return self::FAILURE;
        }

        $modelFiles = $this->scanModelFiles();
        if (empty($modelFiles)) {
            ConsoleOutputStyles::error($output, '未发现任何模型文件（搜索 app/modules/*/model/*.php 与 app/model/*.php）');
            return self::FAILURE;
        }

        $prefix = (string)config('database.connections.mysql.prefix', '');
        $ok = 0;
        $skip = 0;
        $fail = 0;

        foreach ($modelFiles as $path) {
            $code = $this->processModelFile($path, $prefix, $dry, $output);
            if ($code === self::SUCCESS) $ok++;
            elseif ($code === self::INVALID) $skip++; // 用 INVALID 表示跳过（比如没表或无法解析）
            else $fail++;
        }

        ConsoleOutputStyles::info($output, "完成：成功 {$ok} 个，跳过 {$skip} 个，失败 {$fail} 个。");
        return $fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 扫描模型文件（模块目录与旧目录）
     * @return string[]
     */
    private function scanModelFiles(): array
    {
        $paths = [];
        $paths = array_merge($paths, glob(app_path('modules/*/*/model/*.php')) ?: []);
        $paths = array_merge($paths, glob(app_path('modules/*/model/*.php')) ?: []);
        $paths = array_merge($paths, glob(app_path('model/*.php')) ?: []);
        // 去重
        return array_values(array_unique($paths));
    }

    /**
     * 处理单个模型文件：解析类名/表名 -> 取字段 -> 写入 $fillable
     */
    private function processModelFile(string $file, string $prefix, bool $dry, OutputInterface $output): int
    {
        $src = @file_get_contents($file);
        if ($src === false) {
            ConsoleOutputStyles::error($output, "读取失败：{$file}");
            return self::FAILURE;
        }

        // 解析命名空间与类名
        [$ns, $class] = $this->parseNamespaceAndClass($src);
        if (!$class) {
            ConsoleOutputStyles::error($output, "无法解析类名：{$file}");
            return self::FAILURE;
        }

        // 解析 $table；没有则用类名推断（snake_plural + 前缀）
        $table = $this->parseTableProperty($src);
        if (!$table) {
            $table = $prefix . $this->inferTableFromClass($class);
        }

        // 读取表字段
        $columns = [];
        try {
            $plain = $this->stripPrefix($table, $prefix);
            $columns = Db::schema()->getColumnListing($plain);
        } catch (\Throwable $e) {
            // 跳过：模型可能不是 Eloquent 或暂未建表
            ConsoleOutputStyles::warning($output, "跳过（无法读取表 {$table} 字段）：{$file}（{$e->getMessage()}）");
            return self::INVALID;
        }

        if (empty($columns)) {
            ConsoleOutputStyles::warning($output, "跳过（表 {$table} 不存在或无字段）：{$file}");
            return self::INVALID;
        }

        // 过滤系统/受保护字段
        $guarded = ['id', 'created_at', 'deleted_at', 'createdAt', 'updatedAt', 'deletedAt'];
        $fillableCols = array_values(array_filter($columns, fn($c) => !in_array($c, $guarded, true)));

        // 生成短数组代码
        $fillableCode = $this->toShortArraySyntax($fillableCols, 4);

        // 替换/注入
        $new = $this->injectFillable($src, $class, $fillableCode);

        if ($new === null) {
            ConsoleOutputStyles::error($output, "正则替换失败：{$file}");
            return self::FAILURE;
        }

        if ($dry) {
            ConsoleOutputStyles::info($output, "[预览] {$file}  将写入 \$fillable 共 " . count($fillableCols) . " 个字段");
            return self::SUCCESS;
        }

        if (@file_put_contents($file, $new) !== false) {
            ConsoleOutputStyles::info($output, "OK  写入 {$file}  (\$fillable: " . count($fillableCols) . ")");
            return self::SUCCESS;
        }

        ConsoleOutputStyles::error($output, "写入失败：{$file}");
        return self::FAILURE;
    }

    private function parseNamespaceAndClass(string $php): array
    {
        $ns = null;
        $class = null;

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $php, $m)) {
            $ns = trim($m[1]);
        }
        if (preg_match('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $php, $m)) {
            $class = $m[1];
        }
        return [$ns, $class];
    }

    private function parseTableProperty(string $php): ?string
    {
        // 简易解析：protected $table = 'xxx'; 或 public $table = "xxx";
        if (preg_match('/\b(?:public|protected|private)\s+\$table\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/i', $php, $m)) {
            return $m[1];
        }
        return null;
    }

    private function inferTableFromClass(string $class): string
    {
        // 1) 优先：Laravel/Webman Eloquent 的规则
        if (class_exists(\Illuminate\Support\Str::class)) {
            return \Illuminate\Support\Str::snake(
                \Illuminate\Support\Str::pluralStudly($class)
            );
        }

        // 2) 其次：Doctrine Inflector（与上面底层一致）
        if (class_exists(\Doctrine\Inflector\InflectorFactory::class)) {
            $inflector = \Doctrine\Inflector\InflectorFactory::create()->build();
            $plural    = $inflector->pluralize($class); // 传入 StudlyCase
            // Studly → snake
            $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $plural));
            return $snake;
        }

        // 3) 兜底：简易英文复数规则（够用，避免 batchs 这类错误）
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)); // FooBar -> foo_bar
        if (preg_match('/(s|sh|ch|x|z)$/', $snake)) {
            return $snake . 'es';              // e.g. batch -> batches, box -> boxes
        }
        if (str_ends_with($snake, 'y') && !preg_match('/[aeiou]y$/', $snake)) {
            return substr($snake, 0, -1) . 'ies'; // category -> categories
        }
        return $snake . 's';
    }

    private function stripPrefix(string $table, string $prefix): string
    {
        if ($prefix && str_starts_with($table, $prefix)) {
            return substr($table, strlen($prefix));
        }
        return $table;
    }

    private function toShortArraySyntax(array $items, int $indent = 4): string
    {
        $pad = str_repeat(' ', $indent + 4);
        if (empty($items)) return '[]';

        $quoted = array_map(fn($s) => "'" . str_replace("'", "\\'", $s) . "'", $items);
        $oneLine = '[' . implode(', ', $quoted) . ']';
        if (strlen($oneLine) <= 100) return $oneLine;

        return '[' . PHP_EOL
            . $pad . implode(',' . PHP_EOL . $pad, $quoted) . PHP_EOL
            . str_repeat(' ', $indent) . ']';
    }

    private function injectFillable(string $php, string $class, string $fillableCode): ?string
    {
        // 1) 优先替换 begin/end 区块
        $pattern = '/(\/\/\s*begin_fillable\s*)(.*?)(\s*\/\/\s*end_fillable)/si';
        $replacement = '$1'
            . 'protected $fillable = ' . $fillableCode . ';'
            . '$3';

        if (preg_match($pattern, $php)) {
            return preg_replace($pattern, $replacement, $php);
        }

        // 2) 无区块：插入到 class 首个花括号后
        $classPattern = '/(class\s+' . preg_quote($class, '/') . '\b[^{]*\{)/i';
        $insert = '$1'
            . '//begin_fillable'
            . 'protected $fillable = ' . $fillableCode . ';'
            . '//end_fillable';
        return preg_replace($classPattern, $insert, $php, 1);
    }
}
