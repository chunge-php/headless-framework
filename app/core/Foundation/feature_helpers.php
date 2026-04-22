<?php

namespace app\core\Foundation;

/**
 * 批量注册：给定前缀 + 类 + 方法列表  →  返回 Feature 映射数组
 * @param string              $prefix  如 'user.userInfo.'
 * @param string|object       $class   如 UserinfoFn::class 或 new UserinfoFn()
 * @param array<string>|array<string,string> $methods
 *        既可传 ['getUserInfo','create', ...]
 *        也可传 ['getUserInfo' => 'getUserInfo', 'create' => 'create'] 做别名
 */
function feature_group(string $prefix, $class, array $methods): array
{
    $map = [];
    foreach ($methods as $key => $method) {
        $feat = is_string($key) ? ($prefix . $key) : ($prefix . $method);
        $map[$feat] = [$class, $method];
    }
    return $map;
}
function manifest_group(string $prefix,  array $methods): array
{
    $map = [];
    foreach ($methods as $key => $method) {
        $feat = is_string($key) ? ($prefix . $key) : ($prefix . $method);
        $map[$feat] =$method;
    }
    return $map;
}
