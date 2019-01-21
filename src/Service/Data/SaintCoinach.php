<?php

namespace App\Service\Data;

class SaintCoinach
{
    private const SCHEMA_FILENAME  = __DIR__ . '/../../../data/gametools/SaintCoinach.Cmd/ex.json';
    private const SCHEMA_DIRECTORY = __DIR__ . '/../../../data/gametools/SaintCoinach.Cmd';
    
    /**
     * Get the JSON Schema from SaintCoinach
     */
    public static function schema()
    {
        if (!file_exists(self::SCHEMA_FILENAME)) {
            throw new \Exception("SaintCoinach schema ex.json file missing at: ". self::SCHEMA_FILENAME);
        }
        
        $schema = \GuzzleHttp\json_decode(
            file_get_contents(self::SCHEMA_FILENAME)
        );
        
        return $schema;
    }
    
    /**
     * Get the current extracted schema version, this is
     * the version from the folder, not the one in ex.json
     */
    public static function version()
    {
        $dirs = glob(self::SCHEMA_DIRECTORY . '/*' , GLOB_ONLYDIR);
        
        // there should only be 1, if not, throw exception to sort this
        if (count($dirs) > 1) {
            throw new \Exception("there is more than 1 directory in the SaintCoinach
                extracted location, delete old extractions");
        }
        
        return str_ireplace([self::SCHEMA_DIRECTORY, '/'], null, $dirs[0]);
    }
    
    /**
     * Return the data directory for where stuff is extracted
     */
    public static function directory()
    {
        return self::SCHEMA_DIRECTORY ."/". self::version();
    }
}
