<?php

namespace App\Service\Redis;

/**
 * Access a static Redis Cache
 */
class Redis
{
    const TIME_1_YEAR   = (60 * 60 * 24 * 365);
    const TIME_10_YEAR  = (60 * 60 * 24 * 365 * 10);
    const TIME_30_DAYS  = (60 * 60 * 24 * 30);
    const TIME_7_DAYS   = (60 * 60 * 24 * 7);
    const TIME_24_HOURS = (60 * 60 * 24);
    
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
