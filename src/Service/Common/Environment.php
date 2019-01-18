<?php

namespace App\Service\Common;

use App\Exception\UnauthorizedAccessException;
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
        self::checkValidHostDomain($request);
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

    /**
     * Checks the request came from a valid host, this restricts
     * '/japan/xxx' endpoints to 'lodestone.xivapi.com'
     */
    public static function checkValidHostDomain(Request $request)
    {
        $path = explode('/', $request->getPathInfo());
        
        if (constant(self::CONSTANT) === 'companion') {
            if ($path[1] !== 'companion') {
                throw new UnauthorizedAccessException();
            }
        }
    
        if (constant(self::CONSTANT) === 'lodestone') {
            if ($path[1] !== 'japan') {
                throw new UnauthorizedAccessException();
            }
        }
        
    }
}
