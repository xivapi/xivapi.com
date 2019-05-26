<?php

namespace App\Service\Content;

/**
 * provides a way to hash content "names" into something
 * that can be easily matched, this makes it easier to
 * identify stuff that is parsed from Lodestone.
 */
class ContentHash
{
    // max length of hash, not much game content so it can be small
    const LENGTH = 6;

    public static function hash($value)
    {
        return substr(sha1(strtolower($value)), 0, self::LENGTH);
    }
}
