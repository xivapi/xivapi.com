<?php

namespace App\Service\DataCustom;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Language;
use App\Service\Content\ManualHelper;
use App\Service\Content\DescriptionFormatter;

class SkillDescriptions extends ManualHelper
{
    const PRIORITY = 10000;
    
    /**
     * Restore:
     *
     * php bin/console SaintCoinachRedisCommand 0 1000 1 Action
     * php bin/console SaintCoinachRedisCommand 0 1000 1 Trait
     * php bin/console SaintCoinachRedisCommand 0 1000 1 CraftAction
     * php bin/console SaintCoinachRedisCommand 0 1000 1 Item
     *
     * php bin/console SaintCoinachRedisCustomCommand Transient
     * php bin/console SaintCoinachRedisCustomCommand SkillDescriptions
     */
    
    public function handle()
    {
        $this->handleActions();
        $this->handleTraits();
        $this->handleCraftActions();
        $this->handleItems();
    }
    
    /**
     * Handle all action descriptions
     */
    private function handleActions()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('Action');
    
        foreach ($ids as $id) {
            $key = "xiv_Action_{$id}";
            $action = Redis::Cache(true)->get($key);
        
            $this->formatDescription($action);
        
            // save
            Redis::Cache(true)->set($key, $action, self::REDIS_DURATION);
        }
    }
    
    /**
     * Handle all trait descriptions
     */
    private function handleTraits()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('Trait');
        
        foreach ($ids as $id) {
            $key = "xiv_Trait_{$id}";
            $action = Redis::Cache(true)->get($key);
            
            $this->formatDescription($action);
            
            // save
            Redis::Cache(true)->set($key, $action, self::REDIS_DURATION);
        }
    }
    
    private function handleCraftActions()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('CraftAction');
    
        foreach ($ids as $id) {
            $key = "xiv_CraftAction_{$id}";
            $action = Redis::Cache(true)->get($key);
        
            $this->formatDescription($action);
        
            // save
            Redis::Cache(true)->set($key, $action, self::REDIS_DURATION);
        }

    }
    
    private function handleItems()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('Item');
    
        foreach ($ids as $id) {
            $key = "xiv_Item_{$id}";
            $action = Redis::Cache(true)->get($key);
        
            $this->formatDescription($action);
        
            // save
            Redis::Cache(true)->set($key, $action, self::REDIS_DURATION);
        }
    }

    /**
     * Format descriptions into sexy json entries
     */
    private function formatDescription($object)
    {
        $formatter = new DescriptionFormatter();
        
        // loop through each language and format them into JSON + Simple
        foreach (Language::LANGUAGES as $lang) {
            if (!isset($object->{'Description_'. $lang})) {
                $object->{'Description_'. $lang}     = null;
                $object->{'DescriptionJSON_'. $lang} = null;
                continue;
            }
            
            if (empty($object->{'Description_'. $lang})) {
                continue;
            }
            
            // format descriptions
            [$descriptionJson, $jsonTrue, $jsonFalse] = $formatter->format($object->{'Description_'. $lang});
            $object->{'Description_'. $lang} = $jsonTrue;
            $object->{'DescriptionJSON_'. $lang} = $descriptionJson;
        }
    }
}
