<?php

namespace App\Service\Content;

class LodestoneData
{
    /**
     * Save some lodestone data
     */
    public static function save(string $type, string $filename, $id, $data)
    {
        file_put_contents(
            self::folder($type, $id) .'/'. $filename .'.json',
            json_encode($data)
        );
    }
    
    /**
     * Load some lodestone data
     */
    public static function load(string $type, string $filename, $id)
    {
        $json = null;
        $jsonFilename = self::folder($type, $id) .'/'. $filename .'.json';

        if (file_exists($jsonFilename)) {
            $json = json_decode(
                file_get_contents($jsonFilename)
            );
        }

        return $json;
    }
    
    /**
     * Check for a storage folder, creates it on runtime.
     */
    public static function folder(string $type, string $id)
    {
        $mount    = getenv('SITE_CONFIG_MOUNT');
        $idFolder = substr($id, -4);
        $folder   = "{$mount}/{$type}/{$idFolder}/{$id}";
        
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        
        return $folder;
    }
}
