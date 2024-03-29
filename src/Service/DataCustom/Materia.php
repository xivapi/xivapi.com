<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Materia extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Materia');
    
        foreach ($ids as $id) {
            $key = "xiv_Materia_{$id}";
            $materia = Redis::Cache(true)->get($key);
        
            // attach materia to item
            $this->attachMateriaToItem($materia);
        
            // save
            Redis::Cache(true)->set($key, $materia, self::REDIS_DURATION);
        }
    }
    
    /**
     * Attaches materia info to the item
     */
    private function attachMateriaToItem($materia)
    {
        foreach (range(0,9) as $i) {
            $item   = $materia->{"Item{$i}"};
            $value  = $materia->{"Value{$i}"};
            
            if ($item) {
                $key = "xiv_Item_{$item->ID}";
                $item = Redis::Cache(true)->get($key);
                $item->Materia = [
                    'ID'        => $materia->ID,
                    'BaseParam' => $materia->BaseParam,
                    'Value'     => $value,
                ];
                
                // save
                Redis::Cache(true)->set($key, $item, self::REDIS_DURATION);
            }
        }
    }
}
