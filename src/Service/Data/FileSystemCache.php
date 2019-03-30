<?php

namespace App\Service\Data;

/**
 * Builds data
 */
class FileSystemCache
{
    /** @var array */
    private static $cache = [];

    public static function add($content, $data)
    {
        /**
         * Because SE cannot use normal ids, i force
         * all ids into strings to ensure it is stored
         * as an object and not as an array
         */
        foreach($data as $id => $row) {
            self::$cache[$content]["i{$id}"] = $row;
        }
    }

    /**
     * Get data for a specific content and index
     */
    public static function get($contentName, $id)
    {
        // temp fix
        if ($contentName === 'HousingItemCategory' || $contentName === 'HousingLayoutLimit') {
            return null;
        }
        
        $data = self::$cache[$contentName]["i{$id}"] ?? null;
        
        // cache the file
        if ($data === null) {
            // append file into cache
            self::add($contentName, FileSystem::load($contentName, 'json'));
    
            // grab content
            $data = self::$cache[$contentName]["i{$id}"] ?? null;
        }
        
        return $data;
    }
}
