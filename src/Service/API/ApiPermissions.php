<?php

namespace App\Service\API;

class ApiPermissions
{
    // user permissions, statically held
    const PERMISSION_ADD_LODESTONE_CONTENT = 'lodestone';
    const PERMISSION_HAS_MAPPY_ACCESS = 'mappy';

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
}
