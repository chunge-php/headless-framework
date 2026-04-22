<?php

declare(strict_types=1);

namespace app\core\Foundation;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class RouterAssembler
{
    public function __construct(
        private array $modules,
        private string $prefix = ''
    ) {}

    public function mount(): void
    {
        foreach ($this->modules as $m) {
            // 候选 routes 目录：优先直达 /routes，再补充递归匹配 */routes
            $candidates = [];

            $direct = $m['path'] . '/routes';
            if (is_dir($direct)) {
                $candidates[] = $direct;
            }

            // 递归查找任意层级的 routes 目录（带忽略与深度限制）
            $found = $this->findRoutesDirs($m);
            $candidates = array_values(array_unique(array_merge($candidates, $found)));
            // 简单优先级：/routes > /http/routes > /src/routes > 其他
            usort($candidates, function (string $a, string $b) use ($direct): int {
                $score = static function (string $p) use ($direct): int {
                    return ($p === $direct ? 300 : 0)
                        + (str_contains($p, '/http/routes') ? 200 : 0)
                        + (str_contains($p, '/src/routes')  ? 100 : 0);
                };
                return $score($b) <=> $score($a);
            });

            // 加载每个 routes 目录下的 *.php
            foreach ($candidates as $routesDir) {

                foreach (glob($routesDir . '/*.php') ?: [] as $file) {
                    if ($this->prefix !== '') {
                        \Webman\Route::group('/' . trim($this->prefix, '/'), static fn() => require_once $file);
                    } else {
                        require_once $file;
                    }
                }
            }
        }
    }

    /**
     * 递归查找 $root 下所有名为 "routes" 的目录
     */
    private function findRoutesDirs(array $m): array
    {
        $found = [];
        foreach ($m as $k => $v) {
            if(isset($v['path'])){
                $found[] = $v['path'] . '/routes';
            }
        }
        return $found;
    }
}
