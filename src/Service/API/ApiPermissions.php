<?php

namespace App\Service\API;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ApiPermissions
{
    // user permissions, statically held
    const PERMISSION_COMPANION = 'companion';
    const PERMISSION_LODESTONE = 'lodestone';
    const PERMISSION_MAPPY     = 'mappy';
    const PERMISSION_KING      = 'king';

    private static $permissions = [];

    /**
     * Set API Request Permissions
     */
    public static function set(array $permissions)
    {
        self::$permissions = $permissions;
    }

    /**
     * Get all current permissions
     */
    public static function get()
    {
        return self::$permissions;
    }

    /**
     * Get if the user has permission for a specific permission
     */
    public static function has($permission)
    {
        return in_array($permission, self::$permissions);
    }

    public static function require($permission)
    {
        if (self::has($permission) === false) {
            throw new UnauthorizedHttpException();
        }
    }
}
