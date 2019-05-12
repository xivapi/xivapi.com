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
            $Orchestrion = Redis::Cache()->get($key);
            $Orchestrion->OrchestrionUiparam = Redis::Cache()->get("xiv_OrchestrionUiparam_{$id}");
            
            // save
            Redis::Cache()->set($key, $Orchestrion, self::REDIS_DURATION);
        }
    }
}
