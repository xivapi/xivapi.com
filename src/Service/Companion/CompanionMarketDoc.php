<?php

namespace App\Service\Companion;

use App\Service\Companion\Models\MarketItem;

/**
 * Handles providing and save companion market data
 */
class CompanionMarketDoc
{
    const SAVE_DIRECTORY = __DIR__.'/../../../../companion_data/';

    public function __construct()
    {
        if (!is_dir(self::SAVE_DIRECTORY)) {
            mkdir(self::SAVE_DIRECTORY);
        }
    }

    /**
     * Get market doc
     */
    public function get($serverId, $itemId): MarketItem
    {
        $folder   = $this->getFolder($serverId);
        $filename = "{$folder}/{$itemId}.serialised";

        // default empty item
        $item = new MarketItem($serverId, $itemId);

        if (file_exists($filename) == false) {
            return $item;
        }

        $item = file_get_contents($filename);
        $item = unserialize($item);
        return $item;
    }

    /**
     * Save a market doc
     */
    public function save($serverId, $itemId, MarketItem $item)
    {
        $folder   = $this->getFolder($serverId);
        $filename = "{$folder}/item_{$itemId}.serialised";

        file_put_contents($filename, serialize($item));
    }

    /**
     * Get storage folder (also makes it if it dont exist)
     */
    private function getFolder($serverId)
    {
        $folder = self::SAVE_DIRECTORY;
        $folder = "{$folder}/server_{$serverId}";

        if (is_dir($folder) == false) {
            mkdir($folder, 0775, true);
        }

        return $folder;
    }
}
