<?php

namespace App\Utils;

use Ramsey\Uuid\Uuid;

class Random
{
    /**
     * Return a UUID4
     */
    public static function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Generate a random access key, this is user friendly.
     */
    public static function randomAccessKey(): string
    {
        return str_ireplace('-', null, self::uuid() . self::uuid());
    }

    /**
     * Returns a random string for a given length
     * Source: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
     */
    public static function randomSecureString(int $length = 100): string
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@^&()[]{}_+*=';
        $pieces   = [];
        $max      = strlen($keyspace) - 1;
        
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }

        return implode('', $pieces);
    }
}
