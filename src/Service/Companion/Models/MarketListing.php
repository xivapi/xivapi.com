<?php

namespace App\Service\Companion\Models;

use App\Service\Redis\Redis;

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
     * Build a MarketListing object from SE API response
     */
    public static function build(\stdClass $data): MarketListing
    {
        $obj                        = new MarketListing();
        $obj->ID                    = sha1($data->itemId); // avoid overflow
        $obj->Added                 = time();
        $obj->IsCrafted             = $data->isCrafted;
        $obj->IsHQ                  = $data->hq;
        $obj->PricePerUnit          = $data->sellPrice;
        $obj->Quantity              = $data->stack;
        $obj->PriceTotal            = $obj->PricePerUnit * $obj->Quantity;
        $obj->RetainerID            = 1; // todo - $data->sellRetainerName;
        $obj->CreatorSignatureID    = 1; // todo - $data->signatureName;
        $obj->TownID                = $data->registerTown;
        $obj->StainID               = $data->stain;
        
        if ($data->materia) {
            foreach ($data->materia as $mat) {
                $mat->grade     = (int)$mat->grade;
                $row            = Redis::Cache()->get("xiv_Materia_{$mat->key}");
                $item           = $row->{"Item{$mat->grade}"};
                $obj->Materia[] = $item->ID;
            }
        }
        
        return $obj;
    }
    
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
