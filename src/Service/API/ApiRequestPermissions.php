<?php

namespace App\Service\API;

use App\Entity\User;

class ApiRequestPermissions
{
    /**
     * A list of permissions that each user account can perform
     * @var array
     */
    public static $permissions = [

    ];

    /**
     * Check if a user has permissions, can be called at any time
     */
    public static function hasPermission($setting)
    {
        return self::$permissions[$setting] ?? false;
    }

    /**
     * Set the permissions for the current logged in user
     */
    public static function registerPermissions(User $user)
    {
        foreach(self::$permissions as $permission => $state) {
            self::$permissions[$permission] = in_array($permission, $user->getApiEndpointPermissions());
        }
    }
}
