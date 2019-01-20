<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketItemListing
{
    public $id;
    public $time = 0;
    public $is_crafted = false;
    public $is_hq = false;
    public $price_per_unit;
    public $price_total;
    public $quantity;
    public $retainer_id;
    public $craft_signature_id;
    public $town_id;
    public $stain_id;
    public $materia = [];
}
