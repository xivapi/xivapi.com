<?php

namespace App\Common\Utils;

use Symfony\Component\HttpFoundation\Request;

class Environment
{
    const CONSTANT = 'ENVIRONMENT';

    /**
     * Register the environment and check the host domain
     */
    public static function register(Request $request)
    {
        self::setEnvironmentConstant($request);
    }

    /**
     * Sets the environment variable for prod, staging, local
     */
    public static function setEnvironmentConstant(Request $request)
    {
        $environment = 'prod';
        $host = explode('.', $request->getHost());

        switch($host[0]) {
            case 'staging':
                $environment = 'staging';
                break;

            case 'beta':
                $environment = 'beta';
                break;
        }

        if (isset($host[1]) && $host[1] === 'local') {
            $environment = 'local';
        }

        if (defined(self::CONSTANT) === false) {
            define(self::CONSTANT, $environment);
        }
    }
}
