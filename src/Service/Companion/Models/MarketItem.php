<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketItem
{
    /** @var string */
    public $ID;
    /** @var int */
    public $Server;
    /** @var int */
    public $ItemID;
    /** @var GameItem */
    public $Item;
    /** @var int */
    public $Updated;
    /** @var MarketListing[] */
    public $Prices = [];
    /** @var MarketHistory[] */
    public $History = [];
    
    public function __construct(int $server, int $itemId, ?int $updated = null)
    {
        $this->ID       = "{$server}_{$itemId}";
        $this->Server   = $server;
        $this->ItemID   = $itemId;
        $this->Updated  = $updated;
    }
}
