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

        if ($host[0] === 'staging') {
            $environment = 'staging';
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
        if ($request->getHost() == 'lodestone.xivapi.com' && $path[1] !== 'japan') {
            throw new UnauthorizedAccessException();
        }
    }
}
