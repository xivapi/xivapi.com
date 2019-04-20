<?php

namespace App\Service\Common;

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
    
            case 'companion':
                $environment = 'companion';
                break;
    
            case 'lodestone':
                $environment = 'lodestone';
                break;
        }

        if (isset($host[1]) && $host[1] === 'local') {
            $environment = 'local';
        }

        if (!defined(self::CONSTANT)) {
            define(self::CONSTANT, $environment);
        }
    }
}
