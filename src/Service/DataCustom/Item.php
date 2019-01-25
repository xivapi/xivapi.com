<?php

namespace App\Service\DataCustom;

use App\Service\Data\CsvReader;
use App\Service\Content\ManualHelper;

class Item extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Item');
    
        foreach ($ids as $id) {
            $key = "xiv_Item_{$id}";
            $item = $this->redis->get($key);

            // this is prep for something else
            $item->Materia = $item->Materia ?? null;
            
            // do stuff
            $this->itemLinkItemUiCategoryToItemKind($item);
            $this->redis->set($key, $item, self::REDIS_DURATION);
        }
    }
    
    /**
     * This adds the search category to items that don't have it
     */
    private function itemLinkItemUiCategoryToItemKind($item)
    {
        $itemUiCategory_TO_itemKind = [
            // arms
            1 => [
                1,2,3,4,5,6,7,8,9,10,
                84,87,88,89,96,97,98,
            ],
            // tools
            2 => [
                12,13,14,15,16,17,18,19,20,
                21,22,23,24,25,26,27,28,29,
                30,31,32,33,99
            ],
            // armor
            3 => [
                11,34,35,36,37,38,39
            ],
            // accessories
            4 => [
                40,41,42,43
            ],
            // medicines and meals
            5 => [
                44,45,46,47,
            ],
            // Materials
            6 => [
                48,49,50,51,52,53,54,55,56
            ],
        ];
        
        $itemKindId = false;
        
        // find the slot id
        foreach ($itemUiCategory_TO_itemKind as $kindId => $uiCategoryList) {
            if (isset($item->ItemUICategory->ID) && in_array($item->ItemUICategory->ID, $uiCategoryList)) {
                $itemKindId = $kindId;
                break;
            }
        }
        
        // 7 = other
        $itemKindId     = $itemKindId ?: 7;
        $itemKindCsv    = CsvReader::Get(__DIR__.'/Csv/ItemKind.csv');
        $itemKindCsv    = $itemKindCsv[$itemKindId];
        $item->ItemKind = $itemKindCsv;
    }
}
