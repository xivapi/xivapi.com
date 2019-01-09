<?php

namespace App\Service\Redis;

class Redis
{
    /** @var Cache */
    private static $instance = null;

    /**
     * Get a static cache
     */
    public static function Cache(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new Cache();
            self::$instance->checkConnection();
        }

        return self::$instance;
    }
}
