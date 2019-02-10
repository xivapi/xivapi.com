<?php

namespace App\Service\Companion;

use App\Service\Common\Json;
use App\Service\Redis\Redis;

class CompanionItems
{
    const FILENAME   = __DIR__.'/CompanionItems.json';
    
    /**
     * Provides a list of market items
     */
    public static function items(bool $reset = false)
    {
        // if already cached, use it
        if ($reset === false && file_exists(self::FILENAME)) {
            return Json::open(self::FILENAME);
        }
    
        // build a new market item cache
        $arr   = [];
        $ids   = Redis::Cache()->get('ids_Item');
    
        foreach ($ids as $i => $id) {
            $item = Redis::Cache()->get("xiv_Item_{$id}");
        
            if (isset($item->ItemSearchCategory->ID)) {
                $arr[] = $id;
            }
        }

        // cache list
        Json::save(self::FILENAME, $arr);

        return $arr;
    }
}
