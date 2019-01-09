<?php

namespace App\Service\GamePatch;

/**
 * Manages the Patch increment system
 * php bin/console app:data:patch
 */
class Patch
{
    const FILENAME          = __DIR__ . '/resources/patchlist.json';
    const FILENAME_BACKUP   = __DIR__ . '/resources/patchlist_backup.json';

    /** @var array */
    private $data;

    public function __construct()
    {
        $this->data = file_get_contents(self::FILENAME);
        $this->data = json_decode($this->data);
    }

    /**
     * Save the patch list, this will also create a backup
     */
    public function save()
    {
        // backup old data
        copy(self::FILENAME, self::FILENAME_BACKUP);

        // save new data
        file_put_contents(
            self::FILENAME,
            json_encode($this->data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get the current patch list
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * Get a patch at a specific id
     */
    public function getPatchAtID($id)
    {
        $list = [];
        foreach ($this->data as $patch) {
            $list[$patch->ID] = $patch;
        }
        
        if ($list[$id]) {
            return $list[$id];
        }
        
        throw new \Exception('No patch for id: '. $id);
    }

    /**
     * Get the latest patch
     */
    public function getLatest()
    {
        $latest = end($this->data);
        reset($this->data);
        return $latest;
    }

    /**
     * Add a new patch
     */
    public function create($version, $name, $banner, $expansion, $isExpansion)
    {
        $patch = [
            'Banner'        => $banner,
            'ID'            => $this->getLatest()->ID + 1,
            'Url'           => "/patch+{$version}",
            'ExVersion'     => $expansion,
            'IsExpansion'   => $isExpansion ? true : false,
            'Name_ja'       => $name,
            'Name_en'       => $name,
            'Name_fr'       => $name,
            'Name_de'       => $name,
            'Name_cn'       => $name,
            'Name_kr'       => $name,
            'ReleaseDate'   => time(),
            'Version'       => $version,
        ];

        $this->data[] = $patch;
        $this->save();
    }

    /**
     * Update a patch
     */
    public function update($newPatch)
    {
        foreach ($this->data as $i => $oldPatch) {
            if ($oldPatch->ID === $newPatch->ID) {
                $this->data[$i] = $newPatch;
                break;
            }
        }

        $this->save();
    }

    /**
     * Delete a patch
     */
    public function delete($patch)
    {
        foreach ($this->data as $i => $oldPatch) {
            if ($oldPatch->ID === $patch->ID) {
                unset($this->data[$i]);
                break;
            }
        }

        $this->save();
    }

}
