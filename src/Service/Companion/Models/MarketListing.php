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
    public $RetainerName;
    public $CreatorSignatureID;
    public $CreatorSignatureName;
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
        $obj->IsCrafted             = (int)($data->isCrafted ? 1 : 0);
        $obj->IsHQ                  = (int)($data->hq ? 1 : 0);
        $obj->PricePerUnit          = (int)$data->sellPrice;
        $obj->Quantity              = (int)$data->stack;
        $obj->PriceTotal            = (int)($data->sellPrice * $data->stack);
        $obj->TownID                = (int)$data->registerTown;
        $obj->StainID               = (int)$data->stain;

        // these are internally tracked ids
        $obj->RetainerID            = $data->_retainerId;
        $obj->RetainerName          = $data->sellRetainerName;
        $obj->CreatorSignatureID    = $data->_creatorSignatureId;
        $obj->CreatorSignatureName  = $data->signatureName;
    
        if ($data->materia) {
            foreach ($data->materia as $mat) {
                $mat->grade     = (int)$mat->grade;
                $row            = Redis::Cache()->get("xiv_Materia_{$mat->key}");
                $item           = $row->{"Item{$mat->grade}"};
                $obj->Materia[] = (int)$item->ID;
            }
        }
        
        return $obj;
    }
}
