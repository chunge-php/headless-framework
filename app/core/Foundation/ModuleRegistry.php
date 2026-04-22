<?php

declare(strict_types=1);

namespace app\core\Foundation;

final class ModuleRegistry
{
    /** @var array<string,array{path:string, manifest:array}> */
    private array $modules = [];

    public function __construct(private string $modulesRoot)
    {
        $this->scanModules($modulesRoot);
    }
    // in ModuleRegistry.php

    /**
     * 扫描模块并加载 manifest（优先 .php，回退 .json）
     */
    private function scanModules(string $modulesRoot): void
    {
        foreach (glob($modulesRoot . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            [$manifest, $origin] = $this->loadManifest($dir);
            if ($manifest === null) {
                continue;
            }
            $id = basename($dir);
            $this->modules[$id] = [
                'path'     => $dir,
                'manifest' => $manifest,
                'origin'   => $origin, // 'php' | 'json'
            ];
        }
        foreach (glob($modulesRoot . '/*/*', GLOB_ONLYDIR) ?: [] as $dir) {
            [$manifest, $origin] = $this->loadManifest($dir);
            if ($manifest === null) {
                continue;
            }
            $path = str_replace('\\', '/', $dir);
            $pos  = strripos($path, '/modules/');
            $after = $pos === false ? '' : ltrim(substr($path, $pos + strlen('/modules/')), '/');
            list($id, $pid) =   explode('/', $after);
            $this->modules[$id][$pid] = [
                'path'     => $dir,
                'manifest' => $manifest,
                'origin'   => $origin, // 'php' | 'json'
            ];
        }
    }
    /**
     * 递归加载模块清单，支持 manifest.hf.php / manifest.hf.json
     * 返回 [array $data, 'php'|'json'] 或 [null, null]
     */
    private function loadManifest(string $moduleDir): array
    {
        [$file, $type] = $this->findBestManifestFile(
            $moduleDir,
            maxDepth: 6,
            ignore: ['vendor', 'node_modules', '.git']
        );

        if ($file === null) {
            return [null, null];
        }

        if ($type === 'php') {
            /** @var mixed $data */
            $data = require $file;
            if (!is_array($data)) {
                throw new \RuntimeException("{$file} 必须 return 数组");
            }
            return [$data, 'php'];
        }

        // json
        $txt  = (string) file_get_contents($file);
        $data = json_decode($txt, true);
        if (!is_array($data)) {
            throw new \RuntimeException("{$file} 解析失败（非 JSON 或内容非对象）");
        }
        return [$data, 'json'];
    }

    /**
     * 在 $root 下递归查找最优 manifest 文件。
     * 返回 [string|null $path, 'php'|'json'|null]
     */
    private function findBestManifestFile(
        string $root,
        int $maxDepth = 6,
        array $ignore = []
    ): array {
        if (!is_dir($root)) {
            return [null, null];
        }

        // 先尝试根路径直达（最快路径）
        $rootPhp  = rtrim($root, '/\\') . '/manifest.hf.php';
        $rootJson = rtrim($root, '/\\') . '/manifest.hf.json';
        if (is_file($rootPhp)) {
            return [$rootPhp, 'php'];
        }
        if (is_file($rootJson)) {
            return [$rootJson, 'json'];
        }

        // 递归收集候选
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                static function (\SplFileInfo $file) use ($ignore): bool {
                    return !($file->isDir() && in_array($file->getFilename(), $ignore, true));
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $candidates = []; // [['path'=>..., 'type'=>'php|json', 'depth'=>int]]
        foreach ($rii as $file) {
            if ($rii->getDepth() > $maxDepth) {
                continue;
            }
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if ($name !== 'manifest.hf.php' && $name !== 'manifest.hf.json') {
                continue;
            }
            $type = $name === 'manifest.hf.php' ? 'php' : 'json';
            $path = $file->getPathname();

            // 仅允许在 root 内部（防止符号链接越界）
            $realRoot = realpath($root) ?: $root;
            $realPath = realpath($path) ?: $path;
            if (strpos($realPath, rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0) {
                continue;
            }

            $candidates[] = [
                'path'  => $path,
                'type'  => $type,
                'depth' => $rii->getDepth(),
            ];
        }

        if (!$candidates) {
            return [null, null];
        }

        // 计算权重：路径短优先、目录偏好、php优先
        $scoreFn = static function (array $c): int {
            $p = str_replace('\\', '/', $c['path']);
            $score  = 0;
            $score += ($c['type'] === 'php') ? 50 : 0;                // php > json
            $score += (str_contains($p, '/config/')) ? 30 : 0;        // 偏好 config
            $score += (str_contains($p, '/http/'))   ? 15 : 0;        // 次偏好 http
            // 路径越短越靠近 root（近似衡量“接近根”的程度）
            $score += max(0, 200 - (int)strlen($p));
            // 深度越浅越优先
            $score += max(0, 100 - (int)$c['depth'] * 10);
            return $score;
        };

        usort($candidates, static fn($a, $b) => $scoreFn($b) <=> $scoreFn($a));

        $best = $candidates[0];
        return [$best['path'], $best['type']];
    }

    /** @return array<int,array{id:string,path:string,manifest:array}> */
    public function resolved(): array
    {
        $list = [];
        foreach ($this->modules as $id => $m) {
            $list[] = ['id' => $id] + $m;
        }
        usort($list, fn($a, $b) => strcmp($a['id'], $b['id']));
        return $list;
    }

    /** 模块依赖校验（仅校验 requires.modules），返回问题数组，空数组=通过 */
    public function validateModuleRequires(): array
    {
        $problems = [];
        $new_models = [];
        foreach ($this->modules as $id => $m) {
            $requires = $m['manifest']['requires']['modules'] ?? [];
            if ($requires) {
                $new_models[] = [
                    'name' => $id,
                    'modules' => $requires
                ];
            }
            foreach ($m as $ks => $vs) {
                if (isset($vs['manifest']['requires']['modules']) && $vs['manifest']['requires']['modules']) {
                    $modules2 = $vs['manifest']['requires']['modules'];
                    $new_models[] =  [
                        'name' => $vs['manifest']['name'],
                        'modules' => $modules2
                    ];
                }
            }
        }
        foreach ($new_models as $k => $v) {
            foreach ($v['modules'] as $k2 => $v2) {
                $arr =  explode('.', $k2);
                $actual = '0.0.0';
                $dep = '未知';
                if (count($arr) == 1) {
                    if (!isset($this->modules[$arr[0]])) {
                        $problems[] = "当前模块 {$v['name']} 缺少依赖模块 {$arr[0]}（要求 {$k2}）";
                        continue;
                    }
                    if ($this->modules[$arr[0]]['manifest']['name'] != $k2) {
                        continue;
                    }
                    $actual = $this->modules[$arr[0]]['manifest']['version'] ?? '0.0.0';
                    $dep = $arr[0];
                } elseif (count($arr) == 2) {
                    if (!isset($this->modules[$arr[0]][$arr[1]])) {
                        $problems[] = "当前模块 {$v['name']} 缺少依赖模块 {$arr[1]}（要求 {$k2}）";
                        continue;
                    }
                    $actual = $this->modules[$arr[0]][$arr[1]]['manifest']['version'] ?? '0.0.0';
                    $dep = $arr[1];
                }
                if (!$this->versionSatisfies($actual, $v2)) {
                    $problems[] = "当前模块 {$v['name']} 依赖 {$dep} {$v2}，实际为 {$actual}";
                }
            }
        }
        return $problems;
    }

    /** 功能点依赖校验（requires.features），需要在 FeatureLoader::mount 之后调用 */
    public function validateFeatureRequires(): array
    {
        $problems = [];
        $data =$this->resolved();
        $new_models = [];
        foreach ($data as $id => $m) {
            $requires = $m['manifest']['requires']['features'] ?? [];
            if ($requires) {
                $new_models[] = [
                    'name' => $id,
                    'features' => $requires
                ];
            }
            foreach ($m as $ks => $vs) {
                if (isset($vs['manifest']['requires']['features']) && $vs['manifest']['requires']['features']) {
                    $modules2 = $vs['manifest']['requires']['features'];
                    $new_models[] =  [
                        'name' => $vs['manifest']['name'],
                        'features' => $modules2
                    ];
                }
            }
        }
        foreach ($new_models as $k => $v) {
            foreach ($v['features'] as $k2 => $v2) {
                if (!FeatureRegistry::has($k2)) {
                    $problems[] = "当前模块 {$v['name']} 需要功能点 {$v2} 功能键{$k2}，但未注册";
                }
            }
        }
        return $problems;
    }

    private function versionSatisfies(string $actual, string $require): bool
    {
        $require = trim($require);
        if ($require === '' || $require === '*') return true;
        if (str_starts_with($require, '>=')) {
            return version_compare($actual, substr($require, 2), '>=');
        }
        if (str_starts_with($require, '^')) {
            $major = explode('.', substr($require, 1))[0] ?? '';
            return $major !== '' && str_starts_with($actual, $major . '.');
        }
        return $actual === $require;
    }
    public function validateProvidedFeaturesImplemented(): array
    {
        $problems = [];
        $registered = \app\core\Foundation\FeatureRegistry::names(); // 已注册的功能点名数组
        $registeredSet = array_fill_keys($registered, true);
        foreach ($this->resolved() as  $m) {
            $declared = $m['manifest']['provides']['features'] ?? [];
            if ($declared) {
                foreach ($declared as $featName => $_desc) {
                    if (empty($registeredSet[$featName])) {
                        $problems[] = "模块 {$m['id']} 声明提供功能点 {$featName}，但未在 features/registry.php 中注册";
                    }
                }
                foreach ($m as $k => $v) {
                    $declared2 = $v['manifest']['provides']['features'] ?? [];
                    if ($declared2) {
                        foreach ($declared2 as $featName => $_desc) {
                            if (empty($registeredSet[$featName])) {
                                $problems[] = "模块 {$m['id']} 声明提供功能点 {$featName}，但未在 features/registry.php 中注册";
                            }
                        }
                    }
                }
            } else {
                foreach ($m as $k => $v) {
                    $declared2 = $v['manifest']['provides']['features'] ?? [];
                    if ($declared2) {
                        foreach ($declared2 as $featName => $_desc) {
                            if (empty($registeredSet[$featName])) {
                                $problems[] = "模块 {$m['id']} 声明提供功能点 {$featName}，但未在 features/registry.php 中注册";
                            }
                        }
                    }
                }
            }
        }
        return $problems;
    }
}
