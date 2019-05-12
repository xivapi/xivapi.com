<?php

namespace App\Service\Companion\Models;

use App\Common\Service\Redis\Redis;

/**
 * This is a JSON Model
 */
class GameItem
{
    public $ID;
    public $Name_en;
    public $Name_fr;
    public $Name_de;
    public $Name_ja;
    public $Icon;
    public $LevelItem;
    public $Rarity;

    public static function build(int $itemId): GameItem
    {
        $item = Redis::Cache()->get("xiv_Item_{$itemId}");

        $obj            = new GameItem();
        $obj->ID        = $item->ID;
        $obj->Name_en   = $item->Name_en;
        $obj->Name_fr   = $item->Name_fr;
        $obj->Name_de   = $item->Name_de;
        $obj->Name_ja   = $item->Name_ja;
        $obj->Icon      = $item->Icon;
        $obj->LevelItem = $item->LevelItem;
        $obj->Rarity    = $item->Rarity;

        return $obj;
    }
}
