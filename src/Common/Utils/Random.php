<?php

namespace App\Common\Utils;

use Ramsey\Uuid\Uuid;

class Random
{
    const KEYSPACE_NORMAL   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const KEYSPACE_EXTENDED = '!@^&()[]{}_+*=';

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
    public static function randomSecureString(int $length = 100, string $keyspace = null): string
    {
        $keyspace = $keyspace ?: self::KEYSPACE_NORMAL . self::KEYSPACE_EXTENDED;
        $pieces   = [];
        $max      = strlen($keyspace) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $pieces [] = $keyspace[random_int(0, $max)];
        }

        return implode('', $pieces);
    }

    /**
     * Returns a human friendly code using just the normal keyspace.
     */
    public static function randomHumanUniqueCode(int $length = 8): string
    {
        return self::randomSecureString($length, self::KEYSPACE_NORMAL);
    }
}
