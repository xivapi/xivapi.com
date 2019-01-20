<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketHistoryListing
{
    public $id;
    public $time = 0;
    public $character_name;
    public $is_hq = false;
    public $price_per_unit;
    public $price_total;
    public $quantity;
    public $purchase_date;
}
