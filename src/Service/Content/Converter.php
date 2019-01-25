<?php

namespace App\Service\Content;

class Converter
{
    // max length of hash, not much game content so it can be small
    const LENGTH = 6;
    
    public static function hash($value)
    {
        return substr(sha1(strtolower($value)), 0, self::LENGTH);
    }
}
