<?php
declare(strict_types=1);

namespace app\bootstrap;
date_default_timezone_set('America/New_York');
use Workerman\Worker;
use app\core\Foundation\Autowire;
use app\core\Foundation\FeatureLoader;
use app\core\Foundation\FeatureRegistry;
use app\core\Foundation\ModuleRegistry;
use support\Redis;

final class FeatureAndModuleCheck
{
    public static function start($worker = null): void
    {
        // === 1) 所有 worker 都要执行：功能点注册（关键！） ===
        $reg     = new ModuleRegistry(app_path('modules'));
        $modules = $reg->resolved();

        // 先设置 DI 解析器（如果你需要自动注入）
        if (method_exists(FeatureRegistry::class, 'setResolver')) {
            FeatureRegistry::setResolver(fn(string $class) => Autowire::make($class));
        }

        // 每个进程都要挂载 features（把 callable 放进本进程的 FeatureRegistry::$map）
        FeatureLoader::mount($modules);

        // === 2) 只有 webman 的 0 号 worker 做一次性校验与打印 ===
        if (!$worker || !property_exists($worker, 'name')) {
            return;
        }
        if ( !in_array($worker->name,['sms_posalon','webman']) || (property_exists($worker, 'id') && $worker->id !== 0)) {
            return;
        }

        // 模块依赖校验
        $modProblems = method_exists($reg, 'validateModuleRequires') ? $reg->validateModuleRequires() : [];
        if ($modProblems) {
            self::printFail("模块依赖检查失败", $modProblems);
            Worker::stopAll(1);
            return;
        }

        // 功能点依赖/提供校验
        $featNeedProblems    = method_exists($reg, 'validateFeatureRequires') ? $reg->validateFeatureRequires() : [];
        $featProvideProblems = method_exists($reg, 'validateProvidedFeaturesImplemented') ? $reg->validateProvidedFeaturesImplemented() : [];
        $problems = array_merge($featNeedProblems, $featProvideProblems);

        if ($problems) {
            self::printFail("功能点检查失败", $problems);
        } else {
            self::printOk("模块与功能点检查通过（仅执行一次）");
            self::printOk("清除缓存");
            Redis::flushdb();//清除所有
        }
    }

    private static function printOk(string $msg): void
    {
        echo "\033[32m✅ {$msg}\033[0m\n";
    }
    private static function printFail(string $title, array $items): void
    {
        echo "\n\033[31m❌ {$title}：\033[0m\n";
        foreach ($items as $p) { echo "  - {$p}\n"; }
        echo "\n";
    }
}
