<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketHistory
{
    public $ID;
    public $Added = 0;
    public $PurchaseDate;
    public $PurchaseDateMs;
    public $CharacterID;
    public $CharacterName;
    public $IsHq = false;
    public $PricePerUnit;
    public $PriceTotal;
    public $Quantity;
    
    /**
     * Build a MarketHistory object from SE API response
     */
    public static function build(string $id, \stdClass $data): MarketHistory
    {
        $obj                 = new MarketHistory();
        $obj->ID             = $id;
        $obj->Added          = time();
        $obj->PurchaseDate   = (int)(round($data->buyRealDate / 1000, 0));
        $obj->PurchaseDateMs = (int)$data->buyRealDate;
        $obj->IsHq           = (bool)($data->hq ? true : false);
        $obj->PricePerUnit   = (int)$data->sellPrice;
        $obj->Quantity       = (int)$data->stack;
        $obj->PriceTotal     = (int)($data->sellPrice * $data->stack);
        
        // these are internally tracked ids
        $obj->CharacterID    = $data->_characterId;
        $obj->CharacterName  = $data->buyCharacterName;
        
        return $obj;
    }
}
