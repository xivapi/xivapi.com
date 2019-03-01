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
        $obj                = new MarketHistory();
        $obj->ID            = $id;
        $obj->Added         = time();
        $obj->PurchaseDate  = round($data->buyRealDate / 1000, 0);
        $obj->CharacterID   = 1; // todo
        $obj->CharacterName = $data->buyCharacterName;
        $obj->IsHq          = $data->hq;
        $obj->PricePerUnit  = $data->sellPrice;
        $obj->Quantity      = $data->stack;
        $obj->PriceTotal    = $obj->PricePerUnit * $obj->Quantity;
        
        return $obj;
    }
    
    /**
     * Used for testing purposes.
     */
    public function randomize(): self
    {
        $this->ID            = mt_rand(1,9999999999);
        $this->Added         = time();
        $this->PurchaseDate  = time();
        $this->IsHq          = mt_rand(0,100) % 4 == 0;
        $this->CharacterID   = 730968;
        $this->CharacterName = 'Premium Virtue';
        $this->PricePerUnit  = mt_rand(1,9999);
        $this->Quantity      = mt_rand(1,999);
        $this->PriceTotal    = $this->PricePerUnit * $this->Quantity;
        
        return $this;
    }
}
