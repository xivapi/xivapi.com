<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Orchestrion extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $ids = $this->getContentIds('Orchestrion');
    
        foreach ($ids as $id) {
            $key = "xiv_Orchestrion_{$id}";
            
            // append OrchestrationUiParam
            $Orchestrion = Redis::Cache(true)->get($key);
            $Orchestrion->OrchestrionUiparam = Redis::Cache(true)->get("xiv_OrchestrionUiparam_{$id}");
            
            // save
            Redis::Cache(true)->set($key, $Orchestrion, self::REDIS_DURATION);
        }
    }
}
