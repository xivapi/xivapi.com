<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class InstanceContent extends ManualHelper
{
    const PRIORITY = 20;

    const CONTENT_LINK_TYPES = [
        1 => 'InstanceContent',
        2 => 'PartyContent',
        3 => 'PublicContent',
        4 => 'GoldSaucerContent',
        5 => 'Unknown',
    ];

    private $contentFinderConditions = [
        'InstanceContent'   => [],
        'PartyContent'      => [],
        'PublicContent'     => [],
        'GoldSaucerContent' => [],
        'Unknown'           => [],
    ];
    
    public function handle()
    {
        // store content finder conditions against their instance content id
        foreach (Redis::Cache()->get('ids_ContentFinderCondition') as $id) {
            $cfc  = Redis::Cache()->get("xiv_ContentFinderCondition_{$id}");

            // skip dummy rows
            if ((int)$cfc->ContentLinkType === 0) {
                continue;
            }

            $id   = $cfc->Content;
            $type = self::CONTENT_LINK_TYPES[$cfc->ContentTypeTargetID];

            $this->contentFinderConditions[$type][$id] = $cfc;
        }
        
        $ids = $this->getContentIds('InstanceContent');
        foreach ($ids as $id) {
            $key = "xiv_InstanceContent_{$id}";
            $instanceContent = Redis::Cache()->get($key);
            
            // set fields
            $instanceContent->ContentFinderCondition = null;
            $instanceContent->ContentMemberType = null;
            $instanceContent->ContentType = null;
            $instanceContent->Icon = null;
            $instanceContent->Banner = null;
            
            $this->addContentFinderCondition($instanceContent);
            $this->addInstanceBosses($instanceContent);

            // save
            Redis::Cache()->set($key, $instanceContent, self::REDIS_DURATION);
        }
    }
    
    /**
     * Add content finder condition data
     */
    private function addContentFinderCondition($instanceContent)
    {
        // ensure the fields exist, even if no data
        $instanceContent->Description_en    = null;
        $instanceContent->Description_ja    = null;
        $instanceContent->Description_de    = null;
        $instanceContent->Description_fr    = null;
        $instanceContent->ContentMemberType = null;
        $instanceContent->ContentType       = null;
        $instanceContent->Icon              = null;
        $instanceContent->Banner            = null;

        $instanceContent->ContentFinderCondition = $this->contentFinderConditions[self::CONTENT_LINK_TYPES[1]][$instanceContent->ID] ?? null;
        if (!$instanceContent->ContentFinderCondition) {
            return;
        }

        // Descriptions
        $descriptions = Redis::Cache()->get("xiv_ContentFinderConditionTransient_{$instanceContent->ContentFinderCondition->ID}");
        $instanceContent->Description_en = $descriptions->Description_en;
        $instanceContent->Description_ja = $descriptions->Description_ja;
        $instanceContent->Description_de = $descriptions->Description_de;
        $instanceContent->Description_fr = $descriptions->Description_fr;

        // Names
        $instanceContent->Name_en = $instanceContent->ContentFinderCondition->Name_en;
        $instanceContent->Name_ja = $instanceContent->ContentFinderCondition->Name_ja;
        $instanceContent->Name_de = $instanceContent->ContentFinderCondition->Name_de;
        $instanceContent->Name_fr = $instanceContent->ContentFinderCondition->Name_fr;
        
        // Content Member Type
        $instanceContent->ContentMemberType = $instanceContent->ContentFinderCondition->ContentMemberType;
        
        // ContentType
        $instanceContent->ContentType   = $instanceContent->ContentFinderCondition->ContentType;
        $instanceContent->Icon          = $instanceContent->ContentType->Icon ?? null;
        $instanceContent->Banner        = $instanceContent->ContentFinderCondition->Image;
    }
    
    /**
     * Add instance bosses
     */
    private function addInstanceBosses($instanceContent)
    {
        // Main boss
        if (isset($instanceContent->BNpcBaseBoss->ID)) {
            $instanceContent->BNpcBaseBoss->BNpcName = Redis::Cache()->get("xiv_BNpcName_{$instanceContent->BNpcBaseBoss->ID}");
        }
    }
}
