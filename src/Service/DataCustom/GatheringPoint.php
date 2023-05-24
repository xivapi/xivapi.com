<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class GatheringPoint extends ManualHelper
{
    const PRIORITY = 21;
    
    public function handle()
    {
        $ids = $this->getContentIds('GatheringPoint');
    
        foreach ($ids as $id) {
            $key = "xiv_GatheringPoint_{$id}";
            
            // append GatheringPointTransient
            $GatheringPoint = Redis::Cache(true)->get($key);
            $GatheringPoint->GatheringPointTransient = Redis::Cache(true)->get("xiv_GatheringPointTransient_{$id}");
            $GatheringPoint->ExportedGatheringPoint = Redis::Cache(true)->get("xiv_ExportedGatheringPoint_{$GatheringPoint->GatheringPointBaseTargetID}");
            
            // save
            Redis::Cache(true)->set($key, $GatheringPoint, self::REDIS_DURATION);
        }
    }
}
