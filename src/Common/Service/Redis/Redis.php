<?php

namespace App\Common\Service\Redis;

use App\Common\Constants\RedisConstants;

class Redis
{
    /** @var RedisCache[] */
    private static $instances = [];
    
    /**
     * Get a static cache for an environment
     */
    public static function cache(string $environment = RedisConstants::LOCAL, int $database = null): RedisCache
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
