<?php

declare(strict_types=1);

namespace app\command;

use app\core\Foundation\FeatureLoader;
use app\core\Foundation\ModuleRegistry;
use Composer\InstalledVersions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use app\core\Foundation\FeatureRegistry; // ★ 新增
use app\core\Foundation\Autowire;        // ★ 新增
#[AsCommand(name: 'module:check', description: '检查模块与功能点依赖，并校验指定/默认依赖清单')]
final class ModuleCheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('modules-path', null, InputOption::VALUE_REQUIRED, '模块根目录', app_path('modules'))
            ->addOption('no-features', null, InputOption::VALUE_NONE, '跳过功能点注册与检查')
            ->addOption('json', null, InputOption::VALUE_NONE, '以 JSON 输出（适合 CI）')
            ->addOption('strict', null, InputOption::VALUE_NONE, '开启严格模式：有 warning 也失败')

            // 依赖检查（新增/默认行为）
            ->addOption('dep', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '点名依赖（vendor/pkg[:constraint]），可多次传')
            ->addOption('dep-file', null, InputOption::VALUE_REQUIRED, '依赖清单文件（return [pkg=>constraint]），默认 config/deps.php')
            ->addOption('use-composer', null, InputOption::VALUE_NONE, '对未指定约束的依赖，从 composer.json 的 require 补全约束');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io          = new SymfonyStyle($input, $output);
        $modulesPath = (string)$input->getOption('modules-path');
        $checkFeat   = !$input->getOption('no-features');
        $asJson      = (bool)$input->getOption('json');
        $strict      = (bool)$input->getOption('strict');

        // ========== 1) 模块 & 功能点检查 ==========
        $reg     = new ModuleRegistry($modulesPath);
        $modules = $reg->resolved();

        $modProblems = method_exists($reg, 'validateModuleRequires') ? $reg->validateModuleRequires() : [];

        $featNeedProblems    = [];
        $featProvideProblems = [];
        if ($checkFeat) {
            // ★★★ 关键：把 resolver 提前到 mount 之前
            FeatureRegistry::setResolver(fn(string $class) => Autowire::make($class));

            if (class_exists(FeatureLoader::class)) {
                try {
                    FeatureLoader::mount($modules);
                } catch (\Throwable $e) {
                    $featProvideProblems[] = 'FeatureLoader 异常：' . $e->getMessage();
                }
            }
            if (method_exists($reg, 'validateFeatureRequires')) {
                $featNeedProblems = $reg->validateFeatureRequires();
            }
            if (method_exists($reg, 'validateProvidedFeaturesImplemented')) {
                $featProvideProblems = array_merge(
                    $featProvideProblems,
                    $reg->validateProvidedFeaturesImplemented()
                );
            }
        }

        // ========== 2) 依赖检查（默认：读取 config/deps.php） ==========
        $depOpts     = (array)$input->getOption('dep');
        $depFileOpt  = (string)($input->getOption('dep-file') ?? '');
        $useComposer = (bool)$input->getOption('use-composer');

        // 默认 dep-file：config/deps.php（当未显式传 --dep/--dep-file 时）
        $defaultDepFile = base_path('config/deps.php');
        $depFileToUse = $depFileOpt !== '' ? $depFileOpt : (empty($depOpts) && is_file($defaultDepFile) ? $defaultDepFile : '');

        [$selectedDeps, $depWarnsParse] = $this->collectSelectedDeps($depOpts, $depFileToUse, $useComposer);
        [$depProblems, $depWarnsCheck, $depsChecked] = $this->checkSelectedDeps($selectedDeps);

        // 汇总
        $problems = [
            'modules'            => array_values($modProblems),
            'features_requires'  => array_values($featNeedProblems),
            'features_provides'  => array_values($featProvideProblems),
            'dependencies'       => array_values($depProblems),
        ];
        $warnings = array_merge($depWarnsParse, $depWarnsCheck);

        $countErrors = count($problems['modules'])
            + count($problems['features_requires'])
            + count($problems['features_provides'])
            + count($problems['dependencies']);

        // 输出
        if ($asJson) {
            $payload = [
                'modules_path'        => $modulesPath,
                'features_checked'    => $checkFeat,
                'selected_deps'       => $selectedDeps,
                'dep_file'            => $depFileToUse ?: null,
                'deps_checked'        => $depsChecked,
                'problems'            => $problems,
                'warnings'            => $warnings,
                'ok'                  => $countErrors === 0 && (!$strict || empty($warnings)),
            ];
            $output->writeln(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $io->title('模块 / 功能点 / 依赖 检查');

            // 模块依赖
            if ($modProblems) {
                $io->section('❌ 模块依赖（requires.modules）');
                foreach ($modProblems as $p) {
                    $io->error($p);
                }
            } else {
                $io->success('模块依赖检查通过');
            }

            // 功能点依赖
            if ($checkFeat) {
                if ($featNeedProblems) {
                    $io->section('❌ 功能点依赖（requires.features）');
                    foreach ($featNeedProblems as $p) {
                        $io->error($p);
                    }
                } else {
                    $io->success('功能点依赖（requires.features）检查通过');
                }
                if ($featProvideProblems) {
                    $io->section('❌ 提供功能点实现（provides.features）');
                    foreach ($featProvideProblems as $p) {
                        $io->error($p);
                    }
                } else {
                    $io->success('提供功能点实现（provides.features）检查通过');
                }
            } else {
                $io->warning('已跳过功能点注册与检查（--no-features）');
            }

            // 指定/默认依赖
            $io->section('🎯 依赖检查来源');
            if (!$selectedDeps) {
                $io->text('（未指定依赖；未找到 config/deps.php；跳过依赖检查）');
            } else {
                if ($depFileToUse) {
                    $io->text("依赖清单文件：{$depFileToUse}");
                } else {
                    $io->text('依赖来自命令行 --dep');
                }

                if ($depProblems) {
                    $io->section('❌ 依赖检查');
                    foreach ($depProblems as $p) {
                        $io->error($p);
                    }
                } else {
                    $io->success('依赖检查通过');
                }

                if ($warnings) {
                    $io->section('⚠️ 警告');
                    foreach ($warnings as $w) {
                        $io->warning($w);
                    }
                }
            }

            if ($countErrors === 0 && (!$strict || empty($warnings))) {
                $io->writeln('<info>所有检查通过 ✅</info>');
            }
        }

        if ($countErrors > 0) return Command::FAILURE;
        if ($strict && !empty($warnings)) return Command::FAILURE;
        return Command::SUCCESS;
    }

    /**
     * 收集“点名依赖”：来自 --dep / --dep-file；若 useComposer=true 且缺约束，则从 composer.json 补全
     * @return array{0: array<string,string>, 1: string[]} [deps, warnings]
     */
    private function collectSelectedDeps(array $depOpts, string $depFile, bool $useComposer): array
    {
        $deps = [];
        $warnings = [];

        // A) --dep 多次
        foreach ($depOpts as $spec) {
            $spec = trim((string)$spec);
            if ($spec === '') continue;
            $pkg = $spec;
            $constraint = '';
            if (str_contains($spec, ':')) {
                [$pkg, $constraint] = explode(':', $spec, 2);
                $pkg = trim($pkg);
                $constraint = trim($constraint);
            }
            if ($pkg === '') continue;
            $deps[$pkg] = $constraint;
        }

        // B) --dep-file
        if ($depFile) {
            if (!is_file($depFile)) {
                $warnings[] = "dep-file 不存在：{$depFile}";
            } else {
                $arr = include $depFile;
                if (!is_array($arr)) {
                    $warnings[] = "dep-file 必须返回 array：{$depFile}";
                } else {
                    foreach ($arr as $pkg => $constraint) {
                        $deps[(string)$pkg] = (string)$constraint;
                    }
                }
            }
        }

        // C) 从 composer.json 补全约束
        if ($useComposer && $deps) {
            $composerJson = base_path('composer.json');
            $composerReq = [];
            if (is_file($composerJson)) {
                $json = json_decode((string)file_get_contents($composerJson), true);
                if (is_array($json) && isset($json['require']) && is_array($json['require'])) {
                    /** @var array<string,string> $composerReq */
                    $composerReq = $json['require'];
                }
            }
            foreach ($deps as $pkg => $constraint) {
                if ($constraint === '' && isset($composerReq[$pkg])) {
                    $deps[$pkg] = (string)$composerReq[$pkg];
                }
            }
        }

        return [$deps, $warnings];
    }

    /**
     * 只检查“点名”的依赖
     * @param array<string,string> $deps [package => constraint]
     * @return array{0: string[], 1: string[], 2: array<string,array{required:string,installed:?string,ok:bool}>}
     */
    /**
     * 只检查“点名”的依赖
     * @param array<string,string> $deps [package => constraint]
     * @return array{0: string[], 1: string[], 2: array<string,array{required:string,installed:?string,ok:bool}>}
     */
    private function checkSelectedDeps(array $deps): array
    {
        $problems = [];
        $warnings = [];
        $checked  = [];

        if (!$deps) {
            return [[], [], []];
        }

        $hasSemver = class_exists(\Composer\Semver\Semver::class);

        // 别名映射：自定义名字 -> Composer 包名
        $alias = [
            'webman' => 'workerman/webman-framework',
            // 需要的话这里再加其他别名
        ];

        foreach ($deps as $pkg => $constraint) {
            $constraint = trim($constraint);
            $pkgOrig = $pkg;

            // ========== A) 平台包：php ==========
            if ($pkg === 'php') {
                $installed = PHP_VERSION; // 如 8.2.9
                $ok = $hasSemver
                    ? \Composer\Semver\Semver::satisfies(ltrim($installed, 'vV'), $constraint ?: '*')
                    : $this->simpleSatisfies(ltrim($installed, 'vV'), $constraint ?: '*');

                if (!$ok) {
                    $problems[] = "PHP 版本不满足：要求 {$constraint}，实际 {$installed}";
                }
                $checked[$pkgOrig] = ['required' => $constraint ?: '*', 'installed' => $installed, 'ok' => $ok];
                continue;
            }

            // ========== B) 平台包：扩展 ext-xxx ==========
            if (str_starts_with($pkg, 'ext-')) {
                $ext = substr($pkg, 4);
                $loaded = extension_loaded($ext);
                $installed = $loaded ? (phpversion($ext) ?: '0.0.0') : null;

                if (!$loaded) {
                    $problems[] = "未安装 PHP 扩展：{$ext}";
                    $checked[$pkgOrig] = ['required' => $constraint ?: '*', 'installed' => null, 'ok' => false];
                    continue;
                }

                $ok = true;
                if ($constraint && $constraint !== '*') {
                    $ok = $hasSemver
                        ? \Composer\Semver\Semver::satisfies(ltrim((string)$installed, 'vV'), $constraint)
                        : $this->simpleSatisfies(ltrim((string)$installed, 'vV'), $constraint);
                }

                if (!$ok) {
                    $problems[] = "扩展版本不满足：{$ext} 要求 {$constraint}，实际 {$installed}";
                }
                $checked[$pkgOrig] = ['required' => $constraint ?: '*', 'installed' => $installed, 'ok' => $ok];
                continue;
            }

            // ========== C) 别名 -> Composer 包名 ==========
            if (isset($alias[$pkg])) {
                $pkg = $alias[$pkg];
            }

            // ========== D) 普通 Composer 依赖 ==========
            if (!class_exists(\Composer\InstalledVersions::class)) {
                $warnings[] = "无法使用 Composer\\InstalledVersions 检查 {$pkg}，请升级 composer-runtime-api";
                $checked[$pkgOrig] = ['required' => $constraint, 'installed' => null, 'ok' => false];
                continue;
            }

            if (!\Composer\InstalledVersions::isInstalled($pkg)) {
                $problems[] = "未安装依赖：composer require {$pkg}" . ($constraint ? "（要求 {$constraint}）" : '');
                $checked[$pkgOrig] = ['required' => $constraint, 'installed' => null, 'ok' => false];
                continue;
            }

            $installed = \Composer\InstalledVersions::getPrettyVersion($pkg)
                ?? \Composer\InstalledVersions::getVersion($pkg)
                ?? null;

            // 未指定约束：只要已安装就 OK
            if ($constraint === '' || $constraint === '*') {
                $checked[$pkgOrig] = ['required' => $constraint ?: '*', 'installed' => $installed, 'ok' => true];
                continue;
            }

            $ok = $hasSemver
                ? \Composer\Semver\Semver::satisfies(ltrim((string)$installed, 'vV'), $constraint)
                : $this->simpleSatisfies(ltrim((string)$installed, 'vV'), $constraint);

            if (!$ok) {
                $problems[] = "依赖版本不满足：{$pkg} 要求 {$constraint}，实际 {$installed}";
            }
            $checked[$pkgOrig] = ['required' => $constraint, 'installed' => $installed, 'ok' => $ok];
        }

        return [$problems, $warnings, $checked];
    }


    /**
     * 简化的 semver 判断（composer/semver 不存在时兜底）
     * 支持：>=x.y.z、^x.y.z、精确
     */
    private function simpleSatisfies(string $actual, string $require): bool
    {
        $actual  = ltrim($actual, 'vV');
        $require = trim($require);

        if ($require === '' || $require === '*') return true;

        if (str_starts_with($require, '>=')) {
            return version_compare($actual, substr($require, 2), '>=');
        }

        if (str_starts_with($require, '^')) {
            $req = ltrim(substr($require, 1), 'vV');
            $major = explode('.', $req)[0] ?? '';
            return $major !== '' && str_starts_with($actual, $major . '.');
        }

        // 精确匹配
        return version_compare($actual, ltrim($require, 'vV'), '==');
    }
}
