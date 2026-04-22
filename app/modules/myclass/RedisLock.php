<?php
namespace app\modules\myclass;


use support\Redis;

/**
 * 通用 Redis 分布式锁
 */
class RedisLock
{
    /**
     * 尝试加锁
     */
    public static function acquire(string $key, string $token, int $ttl = 10): bool
    {
        return (bool) Redis::set($key, $token, ['NX', 'EX' => $ttl]);
    }

    /**
     * 安全释放锁（仅释放当前 token 的锁）
     */
    public static function release(string $key, string $token): void
    {
        $lua = '
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
        ';
        Redis::eval($lua, 1, $key, $token);
    }
}
