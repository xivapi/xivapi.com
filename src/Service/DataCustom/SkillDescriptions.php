<?php

namespace App\Service\DataCustom;

use App\Service\Content\DescriptionFormatter;
use App\Service\Content\ManualHelper;
use App\Service\Common\Language;

class SkillDescriptions extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $this->handleActions();
        $this->handleTraits();
        $this->handleCraftActions();
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
            $action = $this->redis->get($key);
        
            $this->formatDescription($action);
        
            // save
            $this->redis->set($key, $action, self::REDIS_DURATION);
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
            $action = $this->redis->get($key);
            
            $this->formatDescription($action);
            
            // save
            $this->redis->set($key, $action, self::REDIS_DURATION);
        }
    }
    
    private function handleCraftActions()
    {
        $this->io->text(__METHOD__);
        $ids = $this->getContentIds('CraftAction');
    
        foreach ($ids as $id) {
            $key = "xiv_CraftAction_{$id}";
            $action = $this->redis->get($key);
        
            $this->formatDescription($action);
        
            // save
            $this->redis->set($key, $action, self::REDIS_DURATION);
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
            
            // format descriptions
            [$json, $desc] = $formatter->format($object->{'Description_'. $lang});
            $object->{'Description_'. $lang}     = $desc;
            $object->{'DescriptionJSON_'. $lang} = $json;
        }
    }
}
