<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class Leve extends ManualHelper
{
    // run after "CraftLeve"
    const PRIORITY = 125;
    
    public function handle()
    {
        foreach ($this->redis->get("ids_Leve") as $id) {
            $key  = "xiv_Leve_{$id}";
            $leve = $this->redis->get($key);
            // ---------------------------------------------------
            
            // defaults
            $leve->CraftLeve        = null;
            $leve->CompanyLeve      = null;
            $leve->GatheringLeve    = null;
            $leve->BattleLeve       = null;
            
            // CraftLeve = 917500 > 918500
            if ($leve->DataId >= 917500 && $leve->DataId <= 918500) {
                $leve->CraftLeve = $this->redis->get("xiv_CraftLeve_{$leve->DataId}");
            }
            
            // CompanyLeve = 196600 > 196700
            if ($leve->DataId >= 196600 && $leve->DataId <= 196700) {
                $leve->CompanyLeve = $this->redis->get("xiv_CompanyLeve_{$leve->DataId}");
            }
            
            // GatheringLeve = 131070 > 131300
            if ($leve->DataId >= 131070 && $leve->DataId <= 131300) {
                $leve->GatheringLeve = $this->redis->get("xiv_GatheringLeve_{$leve->DataId}");
            }
            
            // BattleLeve = 65530 > 65800
            if ($leve->DataId >= 65530 && $leve->DataId <= 65800) {
                $leve->BattleLeve = $this->redis->get("xiv_BattleLeve_{$leve->DataId}");
            }
    
            // ---------------------------------------------------
            $this->redis->set($key, $leve, self::REDIS_DURATION);
        }
    }
}
