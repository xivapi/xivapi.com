<?php

namespace App\Common\ServicesThirdParty\Discord;

class Discord
{
    private static $classes = [];

    public static function mog(): Mog
    {
        return self::getClass(__METHOD__, Mog::class);
    }

    /**
     * Access a discord bot
     */
    private static function getClass($namespace, $className)
    {
        if (isset(self::$classes[$namespace])) {
            return self::$classes[$namespace];
        }

        self::$classes[$namespace] = new $className();

        return self::$classes[$namespace];
    }
}
