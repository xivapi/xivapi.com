<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class ItemAction extends ManualHelper
{
    const PRIORITY = 20;
    
    /**
     * todo - Process using: https://github.com/viion/ffxiv-datamining/blob/master/research/item_actions.md
     */
    public function handle()
    {
        $ids = $this->getContentIds('ItemAction');

        $this->io->writeln(" -- Updating ItemActions");
        
        foreach ($ids as $id) {
            $key = "xiv_ItemAction_{$id}";
            $itemAction = Redis::Cache()->get($key);
            
            // 20086 == Ornament
            if ($itemAction->Type == 20086) {
                $itemAction->Ornament = Redis::Cache()->get("xiv_Ornament_". $itemAction->Data0);
            }
            
            // save
            Redis::Cache()->set($key, $itemAction, self::REDIS_DURATION);
        }
    
    
    
        $this->io->writeln(" -- Updating all Item entries with new Item Action info (will take a minute)");
        
        // We need to update all items
        $ids = $this->getContentIds('Item');
        
        foreach ($ids as $id) {
            $key = "xiv_Item_{$id}";
            $item = Redis::Cache()->get($key);
            
            // ignore non item action entries
            if (empty($item->ItemAction->ID)) {
                continue;
            }
            
            // try get the item entry
            $itemAction = Redis::Cache()->get("xiv_ItemAction_{$item->ItemAction->ID}");
            
            if (!$itemAction) {
                continue;
            }
            
            // if the ItemAction has an Ornament we will append this item onto the Ornament entry
            if (!empty($itemAction->Ornament)) {
                $ornamentKey    = "xiv_Ornament_{$itemAction->Ornament->ID}";
                $ornament       = Redis::Cache()->get($ornamentKey);
                $ornament->Item = $item;
                Redis::Cache()->set($ornamentKey, $ornament, self::REDIS_DURATION);
            }
            
            $item->ItemAction = $itemAction;
    
            // save
            Redis::Cache()->set($key, $item, self::REDIS_DURATION);
        }
    }
}
