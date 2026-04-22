<?php

declare(strict_types=1);

namespace app\core\Foundation;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FeatureLoader
{
    /**
     * 自动加载各模块 features/registry.php 并注册（递归查找）
     * @param array<int,array{id:string,path:string,manifest:array}> $modules
     */
    public static function mount(array $modules): void
    {
        foreach ($modules as $m) {
            $registries = self::findFeatureRegistries(
                $m['path'],
                maxDepth: 6,
                ignore: ['vendor', 'node_modules', '.git']
            );

            // 优先顺序：{module}/features/registry.php > 路径更短 > depth 更浅
            usort($registries, static function (array $a, array $b): int {
                $score = static function (array $c): int {
                    $p = str_replace('\\', '/', $c['path']);
                    return ($c['is_root'] ? 300 : 0) +
                        max(0, 200 - (int)strlen($p)) +      // 路径越短越优先
                        max(0, 100 - $c['depth'] * 10);       // 深度越浅越优先
                };
                return $score($b) <=> $score($a);
            });

            foreach ($registries as $r) {
                /** @var mixed $items */
                $items = include $r['path'];
                if (!is_array($items)) {
                    throw new \RuntimeException("Feature registry must return array: {$r['path']}");
                }
                FeatureRegistry::registerMany($items);
            }
        }
    }

    /**
     * 递归查找 $root 下所有的 features/registry.php
     * 返回形如 [['path'=>string,'depth'=>int,'is_root'=>bool], ...]
     */
    private static function findFeatureRegistries(
        string $root,
        int $maxDepth = 6,
        array $ignore = []
    ): array {
        $out = [];

        if (!is_dir($root)) {
            return $out;
        }

        $root = rtrim($root, "/\\");
        $rootCandidate = $root . '/features/registry.php';
        if (is_file($rootCandidate)) {
            $out[] = ['path' => $rootCandidate, 'depth' => 0, 'is_root' => true];
        }

        $rii = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                static function (SplFileInfo $file) use ($ignore): bool {
                    // 忽略大目录
                    return !($file->isDir() && in_array($file->getFilename(), $ignore, true));
                }
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $realRootPrefix = (realpath($root) ?: $root) . DIRECTORY_SEPARATOR;

        foreach ($rii as $file) {
            if ($rii->getDepth() > $maxDepth) {
                continue;
            }
            if (!$file->isDir()) {
                continue;
            }

            // 仅在目录名为 features 时检查其下的 registry.php（避免逐文件遍历）
            if ($file->getFilename() !== 'features') {
                continue;
            }

            $featuresDir = $file->getPathname();
            // 防止符号链接越界
            $realFeatures = realpath($featuresDir) ?: $featuresDir;
            if (strpos($realFeatures, $realRootPrefix) !== 0) {
                continue;
            }

            $candidate = $featuresDir . DIRECTORY_SEPARATOR . 'registry.php';
            if (is_file($candidate)) {
                // 标记是否与根路径的 features 相同
                $isRoot = (str_replace('\\', '/', $candidate) === str_replace('\\', '/', $rootCandidate));
                $out[] = [
                    'path'    => $candidate,
                    'depth'   => $rii->getDepth(),
                    'is_root' => $isRoot,
                ];
            }
        }

        // 去重（同路径只保留一份）
        $uniq = [];
        $ret  = [];
        foreach ($out as $row) {
            if (isset($uniq[$row['path']])) continue;
            $uniq[$row['path']] = true;
            $ret[] = $row;
        }

        return $ret;
    }
}
