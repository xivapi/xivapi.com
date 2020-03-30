<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Transient extends ManualHelper
{
    const PRIORITY = 1;
    
    const TRANSIENT_TABLES = [
        // needs offsets
        #'ActionComboRoute',

        // no data yet?
        #'FishingRecordType',

        'Action',
        #'Addon',
        #'BgcArmyAction',
        'Companion',
        'ContentFinderCondition',
        'DpsChallenge',
        'Mount',
        #'PartyContent',
        'Perform',
        #'PvPRank',
        #'PvPSelectTrait',
        'QuickChat',
        'Stain',
        'TerritoryType',
        'Trait',
    ];


    public function handle()
    {
        foreach (self::TRANSIENT_TABLES as $contentName) {
            // Grab transient keys
            $transientKeys = Redis::Cache()->get("ids_{$contentName}Transient");

            if (!$transientKeys) {
                $this->io->text("No Transient for: ". $contentName);
                continue;
            }

            foreach ($transientKeys as $id) {
                $content    = Redis::Cache()->get("xiv_{$contentName}_{$id}");
                $transient  = Redis::Cache()->get("xiv_{$contentName}Transient_{$id}");
                
                unset($transient->ID);
                unset($transient->Url);
                unset($transient->Icon);
                unset($transient->GameContentLinks);
                
                foreach ($transient as $field => $value) {
                    // if it exists for whatever reason, prefix it
                    if (isset($content->{$field})) {
                        $field = 'Transient'. $field;
                        //print_r('Duplicate: '. $field);
                    }
                    
                    $content->{$field} = $value;
                }
                
                // save content
                Redis::Cache()->set("xiv_{$contentName}_{$id}", $content, self::REDIS_DURATION);
            }
        }
    }
}
