<?php

namespace App\Common\Utils;

use Carbon\Carbon;

class SiteVersion
{
    const MAJOR  = 2;
    const MINOR  = 2;
    const OFFSET = 1000;
    
    public static function get()
    {
        [$commits, $hash, $time] = explode("\n", file_get_contents(__DIR__.'/../../../git_version.txt'));
        
        $commitVersion = $commits - self::OFFSET;
        $commitVersion = $commitVersion > 0 ? $commitVersion : 0;
        $commitVersion = str_pad($commitVersion, 2, '0', STR_PAD_LEFT);
        $version       = sprintf('%s.%s.%s', self::MAJOR, self::MINOR, $commitVersion);
        $time          = Carbon::createFromTimestamp($time)->format('jS M - g:i a') . ' (UTC)';

        return (Object)[
            'version'   => $version,
            'commits'   => $commits,
            'hash'      => $hash,
            'hash_min'  => substr($hash, 0, 7),
            'time'      => $time,
        ];
    }
}
