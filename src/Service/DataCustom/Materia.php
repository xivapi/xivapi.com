<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class Materia extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Materia');
    
        foreach ($ids as $id) {
            $key = "xiv_Materia_{$id}";
            $materia = $this->redis->get($key);
        
            // attach materia to item
            $this->attachMateriaToItem($materia);
        
            // save
            $this->redis->set($key, $materia, self::REDIS_DURATION);
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
                $item = $this->redis->get($key);
                $item->Materia = [
                    'ID'        => $materia->ID,
                    'BaseParam' => $materia->BaseParam,
                    'Value'     => $value,
                ];
                
                // save
                $this->redis->set($key, $item, self::REDIS_DURATION);
            }
        }
    }
}
