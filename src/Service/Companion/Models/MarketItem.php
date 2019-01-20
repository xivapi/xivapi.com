<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketItem
{
    /** @var string */
    public $id;
    /** @var int */
    public $server;
    /** @var int */
    public $item_id;
    /** @var int */
    public $total;
    /** @var MarketItemListing[] */
    public $prices = [];
}
