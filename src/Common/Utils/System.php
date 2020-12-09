<?php

namespace App\Common\Utils;

class System
{
    public static function memory()
    {
        return ceil(
            memory_get_peak_usage(true) / 1024 / 1024
        );
    }
}
