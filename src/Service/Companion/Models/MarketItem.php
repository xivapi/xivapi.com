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
    /** @var int */
    public $Updated;
    /** @var MarketListing[] */
    public $Prices = [];
    /** @var MarketHistory[] */
    public $History = [];
    
    
}
