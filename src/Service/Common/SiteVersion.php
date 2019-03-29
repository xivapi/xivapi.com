<?php

namespace App\Service\Common;

use Carbon\Carbon;

/**
 * Provide information for the site
 */
class SiteVersion
{
    const MAJOR = 2;
    const MINOR = 2;
    
    public static function get()
    {
        [$patch, $hash, $time] = explode("\n", file_get_contents(__DIR__.'/../../../git_version.txt'));
        $version = sprintf('%s.%s-%s', self::MAJOR, self::MINOR, $patch);
        
        $time = Carbon::createFromTimestamp($time)->format('jS M - g:i a') . ' (UTC)';

        return (Object)[
            'version'   => $version,
            'hash'      => $hash,
            'time'      => $time,
        ];
    }
}
