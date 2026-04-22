<?php

declare(strict_types=1);

namespace app\command;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use app\modules\myclass\ConsoleOutputStyles;

#[AsCommand(name: 'make:m', description: '在指定模块生成模型和迁移文件（支持自定义模板）')]
final class CreateModel extends Command
{
    private Inflector $inflector;

    protected function configure(): void
    {
        $this->inflector = InflectorFactory::create()->build();

        $this
            ->addArgument('module', InputArgument::REQUIRED, '模块名，如 user/address 等')
            ->addArgument('model',  InputArgument::REQUIRED, '模型名，如 User / AddressBook')
            ->addArgument('describe', InputArgument::OPTIONAL, '描述备注，用于迁移注释')

            // 可选项
            ->addOption('table', null, InputOption::VALUE_REQUIRED, '自定义表名（默认：模型名 snake_case 复数）')
            ->addOption('force', 'f', InputOption::VALUE_NONE, '存在同名文件时强制覆盖')
            ->addOption('no-timestamps', null, InputOption::VALUE_NONE, '模型不包含 created_at/updated_at 字段（仅影响模型模板）')

            // ⭐ 模板路径：默认读取 database/stub/model.stub（迁移）；model 模板可选
            ->addOption('mig-stub', null, InputOption::VALUE_REQUIRED, '迁移模板文件路径，默认 database/stub/model.stub')
            ->addOption('model-stub', null, InputOption::VALUE_REQUIRED, '模型模板文件路径，未提供则使用内置简单模板')
            ->addOption('base', null, InputOption::VALUE_REQUIRED, '模型父类（FQCN），会自动 use 并 alias 为 BaseModel', '\\support\\Model');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (class_exists(ConsoleOutputStyles::class)) {
            ConsoleOutputStyles::initialize($output);
        }

        $module   = trim((string)$input->getArgument('module'));
        $model    = trim((string)$input->getArgument('model'));
        $describe = (string)$input->getArgument('describe') ?: $model . ' model & migration';
        $force    = (bool)$input->getOption('force');
        $noTs     = (bool)$input->getOption('no-timestamps');

        if ($module === '' || $model === '') {
            $this->err($output, '模块名与模型名不能为空');
            return Command::FAILURE;
        }

        // 规范化命名
        $moduleId   = $this->kebabToSnake($module);
        $className  = $this->studly($model);      // UserAddress
        $snake      = $this->snake($className);   // user_address
        $tableName  = (string)($input->getOption('table') ?? '');
        if ($tableName === '') {
            $tableName = $this->inflector->pluralize($snake); // user_addresses
        }

        // 目录
        $moduleRoot  = app_path("modules/{$moduleId}");
        $modelDir    = "{$moduleRoot}/model";
        $migDir      = "{$moduleRoot}/database";

        if (!is_dir($moduleRoot)) {
            $this->err($output, "模块不存在：app/modules/{$moduleId}");
            return Command::FAILURE;
        }
        @mkdir($modelDir, 0777, true);
        @mkdir($migDir,   0777, true);

        // 1) 写模型文件
        $ns       = "app\\modules\\{$moduleId}\\model";
        $modelPhp = "{$modelDir}/{$className}.php";
        $modelStubPath = (string)($input->getOption('model-stub') ?? base_path('database/stub/ModelDemo.stub'));
        $baseFqcn      = (string)$input->getOption('base');
        $modelSrc = is_file($modelStubPath)
            ? $this->renderModelFromStub($modelStubPath, $ns, $className, $tableName, $describe, $baseFqcn)
            : $this->renderModelBuiltin($ns, $className, $tableName, $describe, true /* timestamps 你看需求 */);

        if (file_exists($modelPhp) && !$force) {
            $this->err($output, "模型已存在：{$modelPhp}（使用 --force 覆盖）");
        } else {
            file_put_contents($modelPhp, $modelSrc);
            $this->ok($output, "模型已生成：" . $this->rel($modelPhp));
        }

        // 2) 写迁移文件（从自定义模板 database/stub/model.stub 读取）
        $migStubPath = (string)($input->getOption('mig-stub') ?? base_path('database/stub/model.stub'));
        $timestamp  = date('YmdHis');
        $migClass   = 'Create' . $this->studly($this->inflector->pluralize($className)) . 'Table';
        $migFile    = "{$migDir}/{$timestamp}_create_{$tableName}_table.php";
        $migSrc     = $this->renderMigrationFromStub($migStubPath, $migClass, $tableName, $describe);

        if ($migSrc === null) {
            $this->err($output, "找不到迁移模板：{$migStubPath}");
            return Command::FAILURE;
        }

        if (file_exists($migFile) && !$force) {
            $this->err($output, "迁移已存在：{$migFile}（使用 --force 覆盖）");
        } else {
            file_put_contents($migFile, $migSrc);
            $this->ok($output, "迁移已生成：" . $this->rel($migFile));
        }

        $this->info($output, "下一步可执行：phinx migrate -e production 或你的 install:data 命令");

        return Command::SUCCESS;
    }

    // ===== 模板渲染 =====

    /**
     * 从自定义迁移模板渲染
     * 支持占位符：$className / DummyTableName / DummyDescribe
     */
    private function renderMigrationFromStub(string $stubPath, string $className, string $table, string $describe): ?string
    {
        if (!is_file($stubPath)) {
            return null;
        }
        $tpl = (string)file_get_contents($stubPath);
        // 逐一替换
        $tpl = str_replace('$className', $className, $tpl);
        $tpl = str_replace('DummyTableName', $table, $tpl);
        $tpl = str_replace('DummyDescribe',  $describe, $tpl);
        return $tpl;
    }

 
    /**
     * 从自定义模型模板渲染
     * 支持占位：DummyNamespace / DummyClass / DummyDescribe
     * 另外：若模板里用到了 “extends BaseModel”，本方法会在 namespace 后自动插入
     *   use <FQCN> as BaseModel;
     */
    private function renderModelFromStub(
        string $stubPath,
        string $namespace,
        string $class,
        string $table,
        string $describe,
        string $baseFqcn
    ): string {
        $tpl = (string)file_get_contents($stubPath);

        // 占位符替换
        $namespace = str_replace('/', '\\', $namespace);
        $tpl = str_replace('DummyNamespace', $namespace, $tpl);
        $tpl = str_replace('DummyClass',     $class,     $tpl);
        $tpl = str_replace('DummyDescribe',  $describe,  $tpl);

        // 如果模板里出现了 "extends BaseModel"，自动在 namespace 后插入 use 语句
        if (strpos($tpl, 'extends BaseModel') !== false) {
            $useLine = "use app\model\BaseModel;\n";
            // 在第一行 namespace 后面插入 use
            $tpl = preg_replace(
                '/(namespace\s+[^\;]+;\s*)/m',
                "$1\n{$useLine}",
                $tpl,
                1
            );
        }

        // 你也可以（可选）把表名注入到模型里，如果模板需要的话：
        // $tpl = str_replace('DummyTableName', $table, $tpl);

        return $tpl;
    }


    /**
     * 内置简易模型模板（未提供自定义模板时）
     */
    private function renderModelBuiltin(string $namespace, string $class, string $table, string $describe, bool $timestamps): string
    {
        $base = '\\support\\Model'; // 你可替换为自己的基类
        $tsLine = $timestamps ? "    public \$timestamps = true;\n" : "    public \$timestamps = false;\n";
        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace};

/**
 * {$class} 模型
 * {$describe}
 */
final class {$class} extends {$base}
{
    protected \$table = '{$table}';
{$tsLine}}
PHP;
    }

    // ===== utils =====

    private function studly(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    private function snake(string $name): string
    {
        $name = preg_replace('/(.)(?=[A-Z])/u', '$1_', $name);
        return strtolower((string)$name);
    }

    private function kebabToSnake(string $s): string
    {
        return str_replace('-', '_', $s);
    }

    private function rel(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR);
        return str_replace($base . DIRECTORY_SEPARATOR, '', $path);
    }

    private function ok(OutputInterface $out, string $msg): void
    {
        if (class_exists(ConsoleOutputStyles::class)) {
            ConsoleOutputStyles::info($out, $msg);
        } else {
            $out->writeln("<info>{$msg}</info>");
        }
    }

    private function err(OutputInterface $out, string $msg): void
    {
        if (class_exists(ConsoleOutputStyles::class)) {
            ConsoleOutputStyles::error($out, $msg);
        } else {
            $out->writeln("<error>{$msg}</error>");
        }
    }

    private function info(OutputInterface $out, string $msg): void
    {
        if (class_exists(ConsoleOutputStyles::class)) {
            ConsoleOutputStyles::comment($out, $msg);
        } else {
            $out->writeln("<comment>{$msg}</comment>");
        }
    }
}
