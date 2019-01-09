<?php

namespace App\Service\Data;

/**
 * Handles game file storage
 */
class FileSystem extends DataHelper
{
    private static $cache = [];

    /**
     * List all CSV files for a particular version
     */
    public static function list(string $version)
    {
        $root = __DIR__ .'/../../..'. getenv('GAME_TOOLS_DIRECTORY') . '/SaintCoinach.Cmd';
        $folder = "{$root}/{$version}/raw-exd-all";
        $files = array_diff(scandir($folder), ['..', '.']);

        $tree = (Object)[
            'raw' => [],
            'gamedata' => []
        ];

        foreach($files as $file) {
            // ignore non-CSV files
            if (stripos($file, '.csv') === false) {
                continue;
            }

            // generalize language
            $file = str_ireplace(['.de','.en','.fr','.ja'], '.[lang]', $file);

            // if no .en it is a basic file with no text
            if (stripos($file, '.[lang]') === false) {
                $tree->raw[] = str_ireplace('.csv', null, $file);
                continue;
            }

            $file = str_ireplace('.[lang].csv', null, $file);

            // if not already added, add it
            if (!in_array($file, $tree->gamedata)) {
                $tree->gamedata[] = $file;
            }
        }

        unset ($files);
        return $tree;
    }

    /**
     * Save some content to a json file
     */
    public static function save($filename, $folder, $data)
    {
        $root = __DIR__ .'/../../../'. getenv('GAME_DOCUMENTS_DIRECTORY');
        $folder = "{$root}/{$folder}";

        // check folder exists
        self::checkForFolder($folder);
        $filename = "{$folder}/{$filename}.json";

        // save
        file_put_contents($filename, serialize($data));
        unset($data);
    }
    
    /**
     * Load json content
     */
    public static function load($filename, $folder)
    {
        $root = __DIR__ .'/../../../'. getenv('GAME_DOCUMENTS_DIRECTORY');
        $filename = $folder = "{$root}/{$folder}/{$filename}.json";

        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        $data = file_get_contents($filename);

        if ($data) {
            $data = unserialize($data);
            $data = json_decode(json_encode($data));
        }

        self::$cache[$filename] = $data;
        return $data;
    }

    /**
     * Check if folder exists, otherwise create it
     */
    public static function checkForFolder($folder)
    {
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
    }
}
