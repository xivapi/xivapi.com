<?php

namespace App\Service\Companion\Models;

/**
 * This is a JSON Model
 */
class MarketListing
{
    public $ID;
    public $Added = 0;
    public $IsCrafted = false;
    public $IsHq = false;
    public $PricePerUnit;
    public $PriceTotal;
    public $Quantity;
    public $RetainerID;
    public $CreatorSignatureID;
    public $TownID;
    public $StainID;
    public $Materia = [];
    
    /**
     * Used for testing purposes.
     */
    public function randomize(): self
    {
        $this->ID                   = mt_rand(1,9999999999);
        $this->Added                = time();
        $this->IsCrafted            = mt_rand(0,100) % 4 == 0;
        $this->IsHq                 = mt_rand(0,100) % 4 == 0;
        $this->PricePerUnit         = mt_rand(1,9999);
        $this->Quantity             = mt_rand(1,999);
        $this->PriceTotal           = $this->PricePerUnit * $this->Quantity;
        $this->RetainerID           = mt_rand(1,99999);
        $this->CreatorSignatureID   = mt_rand(1,99999);
        $this->TownID               = mt_rand(1,4);
        $this->StainID              = mt_rand(1,25000);
        
        // add a random number of materia
        if (mt_rand(0,50) % 3 == 0) {
            foreach(range(1, mt_rand(1, 5)) as $i) {
                $this->Materia[] = mt_rand(5604,5724);
            }
        }
        
        return $this;
    }
}
