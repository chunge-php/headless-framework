<?php
namespace app\core\Foundation;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

final class Autowire
{
    /** @var array<string,object> */
    private static array $singletons = [];

    public static function make(string $class): object
    {
        // 接口/抽象：按约定尝试映射  FooInterface -> Foo （同命名空间）
        if ((\interface_exists($class) || self::isAbstract($class))) {
            $impl = self::byConvention($class);
            if ($impl && class_exists($impl)) {
                $class = $impl;
            }
        }

        // 单例缓存
        if (isset(self::$singletons[$class])) {
            return self::$singletons[$class];
        }

        $ref  = new ReflectionClass($class);

        // 静态工厂优先（可选）：make()/create() 无参或仅接收解析器闭包
        foreach (['make','create','fromContainer'] as $factory) {
            if ($ref->hasMethod($factory) && $ref->getMethod($factory)->isStatic()) {
                $m = $ref->getMethod($factory);
                $args = [];
                if ($m->getNumberOfParameters() === 1) {
                    $args[] = fn(string $c) => self::make($c);
                }
                return self::$singletons[$class] = $m->invokeArgs(null, $args);
            }
        }

        $ctor = $ref->getConstructor();
        if (!$ctor || $ctor->getNumberOfParameters() === 0) {
            return self::$singletons[$class] = new $class();
        }

        $deps = [];
        foreach ($ctor->getParameters() as $p) {
            $deps[] = self::resolveParam($class, $p);
        }
        return self::$singletons[$class] = $ref->newInstanceArgs($deps);
    }

    private static function resolveParam(string $consumer, \ReflectionParameter $p)
    {
        $t = $p->getType();

        // 标量/无类型：优先默认值；允许 null；否则报清晰错
        if (!$t instanceof ReflectionNamedType || $t->isBuiltin()) {
            if ($p->isDefaultValueAvailable()) return $p->getDefaultValue();
            if ($t?->allowsNull()) return null;
            throw new \RuntimeException(sprintf(
                '无法为 %s::__construct() 的参数 $%s 自动注入（标量/无默认值）',
                $consumer, $p->getName()
            ));
        }

        $dep = $t->getName();

        // 接口/抽象：尝试约定，否则报错
        if (\interface_exists($dep) || self::isAbstract($dep)) {
            $impl = self::byConvention($dep);
            if ($impl && class_exists($impl)) {
                $dep = $impl;
            } else {
                if ($p->isDefaultValueAvailable()) return $p->getDefaultValue();
                if ($t->allowsNull()) return null;
                throw new \RuntimeException(sprintf(
                    "不能为 %s::__construct() 的参数 $%s(%s) 自动选择实现。\n".
                    "解决：1) 为该类提供静态工厂 make()/create()；或 2) 改为依赖具体类。",
                    $consumer, $p->getName(), $dep
                ));
            }
        }

        return self::make($dep);
    }

    private static function isAbstract(string $class): bool
    {
        return class_exists($class) && (new ReflectionClass($class))->isAbstract();
    }

    /** 约定：\\Foo\\Bar\\BazInterface -> \\Foo\\Bar\\Baz */
    private static function byConvention(string $iface): ?string
    {
        if (str_ends_with($iface, 'Interface')) {
            return substr($iface, 0, -9);
        }
        return null;
    }
}
