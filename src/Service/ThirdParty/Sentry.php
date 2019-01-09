<?php

namespace App\Service\ThirdParty;

use Raven_Client;

class Sentry
{
    /**
     * Install sentry's raven client.
     */
    public static function install()
    {
        if ($sentry = getenv('SENTRY')) {
            (new Raven_Client($sentry))->install();
        }
    }
}
