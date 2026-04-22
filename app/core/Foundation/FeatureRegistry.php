<?php

declare(strict_types=1);

namespace app\core\Foundation;

final class FeatureRegistry
{
    /** @var array<string, callable> */
    private static array $map = [];

    /** @var null|callable(string $class):object 依赖解析器（可接入容器） */
    private static $resolver = null;

    /** 设置依赖解析器：当需要实例化类时会调用此回调 */
    public static function setResolver(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function register(string $name, callable $fn): void
    {
        self::$map[$name] = $fn;
    }

    /** @param array<string, callable|array|string> $items */
    public static function registerMany(array $items): void
    {
        foreach ($items as $name => $callable) {
            try {
                $normalized = self::normalizeCallable($callable, $name);
                self::register($name, $normalized);
            } catch (\Throwable $e) {
                throw new \RuntimeException("功能点 {$name} 注册异常：" . $e->getMessage(), 0, $e);
            }
        }
    }

    public static function has(string $name): bool
    {
        return isset(self::$map[$name]);
    }

    public static function call(string|array|callable $nameOrCallable, ...$args): mixed
    {
        // 直接传入 callable（包括 [Class::class,'method'] / [$obj,'method'] / 闭包 / 'func_name'）
        if (is_array($nameOrCallable) || $nameOrCallable instanceof \Closure || (is_string($nameOrCallable) && (function_exists($nameOrCallable) || str_contains($nameOrCallable, '::') || str_contains($nameOrCallable, '@'))) || (is_object($nameOrCallable) && is_callable($nameOrCallable))) {
            $callable = self::normalizeCallable($nameOrCallable, is_string($nameOrCallable) ? $nameOrCallable : '');
            return $callable(...$args);
        }

        // 通过注册名调用
        if (!self::has($nameOrCallable)) {
            throw new \RuntimeException("Feature '{$nameOrCallable}' not registered");
        }
        return (self::$map[$nameOrCallable])(...$args);
    }

    /** 归一化各种 callable 表达，必要时用 resolver 实例化对象
     *  @return callable
     */
    private static function normalizeCallable($c, string $featureName = '')
    {
        // 1) [$object, 'method']
        if (is_array($c) && isset($c[0], $c[1]) && is_object($c[0])) {
            [$obj, $m] = $c;
            if (!method_exists($obj, $m)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：对象方法不存在 " . get_class($obj) . "::{$m}");
            }
            return [$obj, $m];
        }

        // 2) [ClassName::class, 'method']（可能是实例方法或静态方法）
        if (is_array($c) && isset($c[0], $c[1]) && is_string($c[0])) {
            [$cls, $m] = $c;
            if (!class_exists($cls)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：类不存在 {$cls}");
            }
            if (!method_exists($cls, $m)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：{$cls}::{$m} 方法不存在");
            }
            $ref = new \ReflectionMethod($cls, $m);
            if ($ref->isStatic()) {
                return [$cls, $m];
            }
            $instance = self::$resolver ? (self::$resolver)($cls) : new $cls();
            return [$instance, $m];
        }

        // 3) "Class::method"
        if (is_string($c) && str_contains($c, '::')) {
            [$cls, $m] = explode('::', $c, 2);
            if (!class_exists($cls)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：类不存在 {$cls}");
            }
            if (!method_exists($cls, $m)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：{$cls}::{$m} 方法不存在");
            }
            $ref = new \ReflectionMethod($cls, $m);
            if ($ref->isStatic()) {
                return [$cls, $m];
            }
            $instance = self::$resolver ? (self::$resolver)($cls) : new $cls();
            return [$instance, $m];
        }

        // 4) "Class@method"
        if (is_string($c) && str_contains($c, '@')) {
            [$cls, $m] = explode('@', $c, 2);
            if (!class_exists($cls)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：类不存在 {$cls}");
            }
            if (!method_exists($cls, $m)) {
                throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：{$cls}::{$m} 方法不存在");
            }
            $ref = new \ReflectionMethod($cls, $m);
            if ($ref->isStatic()) {
                return [$cls, $m];
            }
            $instance = self::$resolver ? (self::$resolver)($cls) : new $cls();
            return [$instance, $m];
        }

        // 5) 闭包 / 普通函数名 / 可调用对象
        if ($c instanceof \Closure) {
            return $c;
        }
        if (is_string($c) && function_exists($c)) {
            return $c; // 'strlen' 之类
        }
        if (is_object($c) && is_callable($c)) {
            // 可调用对象：__invoke()
            return $c;
        }

        throw new \InvalidArgumentException("Feature '{$featureName}' 注册失败：不支持的 callable 格式");
    }

    public static function reflection(string $name): \ReflectionFunctionAbstract
    {
        if (!self::has($name)) {
            throw new \RuntimeException("Feature '$name' not registered");
        }
        $c = self::$map[$name];
        if (is_array($c)) {
            [$objOrClass, $m] = $c;
            $cls = is_object($objOrClass) ? get_class($objOrClass) : $objOrClass;
            return new \ReflectionMethod($cls, $m);
        }
        return new \ReflectionFunction(\Closure::fromCallable($c));
    }

    /** @return array<string, callable> */
    public static function all(): array
    {
        return self::$map; // [feature => callable]
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::$map);
    }
}
