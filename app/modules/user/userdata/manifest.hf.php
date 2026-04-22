<?php
use function app\core\Foundation\manifest_group;
            return [
                'name' => 'Userdata',
                'version' => '1.0.0',
                'display' => '用户资料',
                'requires' => [
                    'php' => '>=8.0',
                    'webman' => '>=1.5',
                    'modules' => [],
                    'features' => [],
                ],
                'provides' => [
                    'features' => [],
                ],
            ];