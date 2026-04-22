<?php

namespace app\command;

use app\core\Foundation\FeatureRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use app\core\Foundation\ModuleRegistry;
use app\core\Foundation\FeatureLoader;
use app\core\Foundation\Autowire;        // ★ 新增

class GetRegister extends Command
{
    protected static $defaultName = 'get:fn';

    protected function configure(): void
    {
        $this
            ->setDescription('查看当前已注册的功能点（FeatureRegistry）')
            ->addOption('details', 'd', InputOption::VALUE_NONE, '显示详细目标（类/方法/是否静态）');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // 如你要用自定义样式，打开下一行并注释上面一行：
        // \support\myclass\ConsoleOutputStyles::initialize($output);
        $reg     = new ModuleRegistry(app_path('modules'));
        $modules = $reg->resolved();
        // 1) 先做“模块依赖”检查（缺模块/版本不符直接失败）
        method_exists($reg, 'validateModuleRequires') ? $reg->validateModuleRequires() : [];
        // 2) 自动注册各模块的功能点 —— 之前，先设置 resolver
        FeatureRegistry::setResolver(fn(string $class) => Autowire::make($class)); // ★ 放在 mount 之前
        FeatureLoader::mount($modules); // ★
        $map = FeatureRegistry::all(); // [name => callable]
        $names = array_keys($map);
        // 3) check 指定功能点存在性
        $missing = [];
        // 5) 组装数据
        $rows = [];
        foreach ($names as $n) {
            $info = $this->describeCallable($map[$n] ?? null);
            if ($input->getOption('details')) {
                $rows[] = [
                    $n,
                    $info['kind'],
                    $info['target'],
                    $info['static'] ? 'yes' : 'no',
                ];
            } else {
                $rows[] = [$n, $info['target']];
            }
        }


        // 7) 表格输出
        if ($input->getOption('details')) {
            $io->title('已注册功能点（详细）');
            $io->table(['Feature', 'Kind', 'Target', 'Static'], $rows);
        } else {
            $io->title('已注册功能点');
            $io->table(['Feature', 'Target'], $rows);
        }

        if ($missing) {
            $io->error('缺失功能点：' . implode(', ', $missing));
            return Command::FAILURE;
        }

        $io->success(sprintf('共 %d 个功能点。', count($rows)));
        return Command::SUCCESS;
    }

    /**
     * 规范化并描述 callable
     * @param mixed $c
     * @return array{kind:string,target:string,static:bool,class:?string,method:?string}
     */
    private function describeCallable($c): array
    {
        $out = [
            'kind'   => 'unknown',
            'target' => '(unknown)',
            'static' => false,
            'class'  => null,
            'method' => null,
        ];

        if (is_array($c) && isset($c[0], $c[1])) {
            // [$obj, 'method'] 或 [ClassName::class, 'method']
            [$t, $m] = $c;
            $cls = is_object($t) ? get_class($t) : (is_string($t) ? $t : null);
            if ($cls && is_string($m)) {
                $out['kind']   = is_object($t) ? 'instance' : 'static_or_instance';
                $out['class']  = $cls;
                $out['method'] = $m;
                // 判断是否静态
                try {
                    $ref = new \ReflectionMethod($cls, $m);
                    $out['static'] = $ref->isStatic();
                } catch (\Throwable $e) {
                    // ignore
                }
                $out['target'] = $cls . '::' . $m . ($out['static'] ? ' (static)' : '');
                return $out;
            }
        }

        if ($c instanceof \Closure) {
            $out['kind']   = 'closure';
            $out['target'] = 'Closure';
            return $out;
        }

        if (is_string($c)) {
            // 可能是 "Class@method" 或 "Class::method"
            if (str_contains($c, '@') || str_contains($c, '::')) {
                $sep = str_contains($c, '@') ? '@' : '::';
                [$cls, $m] = explode($sep, $c, 2);
                $out['kind']   = 'string-callable';
                $out['class']  = $cls;
                $out['method'] = $m;
                // 判断静态
                try {
                    $ref = new \ReflectionMethod($cls, $m);
                    $out['static'] = $ref->isStatic();
                } catch (\Throwable $e) {
                    // ignore
                }
                $out['target'] = $cls . '::' . $m . ($out['static'] ? ' (static)' : '');
                return $out;
            }
            // 普通函数名
            $out['kind']   = 'function';
            $out['target'] = $c . '()';
            return $out;
        }

        if (is_object($c) && method_exists($c, '__invoke')) {
            $out['kind']   = 'invokable';
            $out['target'] = get_class($c) . '::__invoke';
            return $out;
        }

        return $out;
    }
}
