<?php

namespace App\Service\Common;

use Carbon\Carbon;

/**
 * Provide information for the site
 */
class SiteVersion
{
    public static function get()
    {
        [$patch, $hash, $time] = explode("\n", file_get_contents(__DIR__.'/../../../git_version.txt'));
    
        $patch   = round($patch / 10);
        $version = sprintf('%s.%s.%s', getenv('VERSION_MAJOR'), getenv('VERSION_MINOR'), $patch);
        
        $time = Carbon::createFromTimestamp($time)->format('jS M - g:i a') . ' (UTC)';

        return (Object)[
            'version'   => $version,
            'hash'      => $hash,
            'time'      => $time,
        ];
    }
}
