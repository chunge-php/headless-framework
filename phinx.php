<?php
$base = base_path(); // 比 __DIR__ 更稳：项目根

$migrationPaths = [];

// ✅ 推荐：模块下的 database/migrations
foreach (glob($base . '/app/modules/*/database', GLOB_ONLYDIR) ?: [] as $d) {
    $migrationPaths[] = $d;
}

// 兼容：模块下的 migration（你旧的放法）
foreach (glob($base . '/app/modules/*/migration', GLOB_ONLYDIR) ?: [] as $d) {
    $migrationPaths[] = $d;
}

// 项目级迁移（可选）
if (is_dir($base . '/app/database/migrations')) {
    $migrationPaths[] = $base . '/app/database/migrations';
}
foreach (glob($base . '/app/modules/*/*/database', GLOB_ONLYDIR) ?: [] as $d) {
    $migrationPaths[] = $d;
}
$tablePrefix = getenv('db_prefix') ?: '';

return [
    'paths' => [
        'migrations' => $migrationPaths,
        'seeds'      => $base . '/app/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => $tablePrefix . 'phinxlogs',
        'default_environment'     => getenv('PHINX_ENV') ?: 'development',
        'development' => [
            'adapter'      => getenv('DB_DRIVER')    ?: 'mysql',
            'host'         => getenv('DB_HOST')      ?: '127.0.0.1',
            'name'         => getenv('DB_DATABASE')  ?: 'app',
            'user'         => getenv('DB_USER_NAME') ?: 'root',
            'pass'         => getenv('DB_PASSWORD')  ?: 'root',
            'port'         => (int)(getenv('DB_PORT') ?: 3306),
            'charset'      => 'utf8mb4',
            'table_prefix' => $tablePrefix,
        ],
        'production' => [
            'adapter'      => getenv('DB_DRIVER')    ?: 'mysql',
            'host'         => getenv('DB_HOST')      ?: '127.0.0.1',
            'name'         => getenv('DB_DATABASE')  ?: 'app',
            'user'         => getenv('DB_USER_NAME') ?: 'root',
            'pass'         => getenv('DB_PASSWORD')  ?: 'root',
            'port'         => (int)(getenv('DB_PORT') ?: 3306),
            'charset'      => 'utf8mb4',
            'table_prefix' => $tablePrefix,
        ],
        'testing' => [
            'adapter'      => getenv('DB_DRIVER')    ?: 'mysql',
            'host'         => getenv('DB_HOST')      ?: '127.0.0.1',
            'name'         => getenv('DB_DATABASE')  ?: 'app',
            'user'         => getenv('DB_USER_NAME') ?: 'root',
            'pass'         => getenv('DB_PASSWORD')  ?: 'root',
            'port'         => (int)(getenv('DB_PORT') ?: 3306),
            'charset'      => 'utf8mb4',
            'table_prefix' => $tablePrefix,
        ],
    ],
    'version_order' => 'creation',
    'templates' => [
        'file' => '%%PHINX_CONFIG_DIR%%/app/database/stub/model.stub',
    ],
];
