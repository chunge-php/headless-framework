<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Configitem',
    'version' => '1.0.0',
    'display' => '单一配置',
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
