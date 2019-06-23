<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketItem
{
    /** @var string */
    public $ID;
    /** @var bool */
    public $IsTracked = false;
    /** @var int */
    public $Server;
    /** @var int */
    public $ItemID;
    /** @var GameItem */
    public $Item;
    /** @var string */
    public $LodestoneID;
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
