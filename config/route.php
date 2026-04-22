<?php

use app\core\Foundation\{ModuleRegistry, RouterAssembler};
use Webman\Route;

$prefix  = getenv('ROUTE_PREFIX') ?: '';
$reg     = new ModuleRegistry(app_path('modules'));
$modules = $reg->resolved();
// 仅装载路由（功能点注册与依赖校验已在 bootstrap 阶段完成一次）
(new RouterAssembler($modules, $prefix))->mount();