<?php

namespace App\WebSockets\BattleBar;

class Json
{
    public static function stringify($data)
    {
        return json_encode($data);
    }

    public static function parse($data)
    {
        return json_decode($data);
    }
}
