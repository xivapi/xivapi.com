<?php

namespace App\Service\Redis;

/**
 * Access a static Redis Cache
 */
class Redis
{
    /** @var RedisCache[] */
    private static $instances = [];
    
    /**
     * Get a static cache for an environment
     */
    public static function Cache(string $environment = RedisCache::LOCAL, int $database = null): RedisCache
    {
        if (!isset(self::$instances[$environment])) {
            self::$instances[$environment] = (new RedisCache())->connect($environment);
        }
        
        if ($database) {
            self::$instances[$environment]->selectDatabase($database);
        }
        
        return self::$instances[$environment];
    }
}
