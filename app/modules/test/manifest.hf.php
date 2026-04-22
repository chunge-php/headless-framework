<?php
use function app\core\Foundation\manifest_group;
            return [
                'name' => 'Test',
                'version' => '1.0.0',
                'display' => '测试管理',
                'requires' => [
                    'php' => '>=8.0',
                    'webman' => '>=1.5',
                    'modules' => [
                        'test'=>'^1.0.0'
                    ],
                    'features' => [],
                ],
                'provides' => [
                    'features' => [],
                ],
            ];