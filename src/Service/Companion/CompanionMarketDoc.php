<?php

namespace App\Service\Companion;

/**
 * Handles providing and save companion market data
 */
class CompanionMarketDoc
{
    const SAVE_DIRECTORY = __DIR__.'/../../../companion_data/';

    public function __construct()
    {
        if (!is_dir(self::SAVE_DIRECTORY)) {
            mkdir(self::SAVE_DIRECTORY);
        }
    }

    /**
     * Get market doc
     */
    public function get($serverId, $itemId)
    {
        $filename = self::SAVE_DIRECTORY . "doc_{$serverId}_{$itemId}.json";

        if (!file_exists($filename)) {
            return null;
        }

        $doc = file_get_contents($filename);
        $doc = json_decode($doc);

        return $doc;
    }

    /**
     * Save a market doc
     */
    public function save($serverId, $itemId, $doc)
    {
        file_put_contents(
            self::SAVE_DIRECTORY . "doc_{$serverId}_{$itemId}.json",
            json_encode($doc)
        );
    }
}
