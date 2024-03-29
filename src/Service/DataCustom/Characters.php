<?php

namespace App\Service\DataCustom;

use App\Service\Content\ContentHash;
use App\Service\Content\ManualHelper;
use App\Common\Service\Redis\Redis;

class Characters extends ManualHelper
{
    const PRIORITY = 50;
    
    private $keys = [];
    
    public function handle()
    {
        // populate game data
        $this->populate('Companion', 'Name_en');
        $this->populate('Mount', 'Name_en');
        $this->populate('Race', 'Name_en', 'NameFemale_en');
        $this->populate('Tribe', 'Name_en', 'NameFemale_en');
        $this->populate('Title', 'Name_en', 'NameFemale_en');
        $this->populate('GrandCompany', 'Name_en');
        $this->populate('GuardianDeity', 'Name_en');
        $this->populate('Town', 'Name_en');
        $this->populate('BaseParam', 'Name_en');
        $this->populate('GCRankGridaniaFemaleText', 'Name_en');
        $this->populate('GCRankGridaniaMaleText', 'Name_en');
        $this->populate('GCRankLimsaFemaleText', 'Name_en');
        $this->populate('GCRankLimsaMaleText', 'Name_en');
        $this->populate('GCRankUldahFemaleText', 'Name_en');
        $this->populate('GCRankUldahMaleText', 'Name_en');
    
        // special ones
        $this->populateParamGrow();
        $this->populateMateria();
        $this->populateItems();
        $this->populateDyes();
        
        Redis::Cache(true)->set('character_keys', $this->keys, self::REDIS_DURATION);
    }
    
    /**
     * Populate common data
     */
    protected function populate($contentName, $column, $femaleColumn = false)
    {
        $this->io->text(__METHOD__ . " {$contentName}");
        
        $data = [];
        foreach (Redis::Cache(true)->get("ids_{$contentName}") as $id) {
            $content = Redis::Cache(true)->get("xiv_{$contentName}_{$id}");
       
            $hash = ContentHash::hash($content->{$column});
            $data[$hash] = $content->ID;
            
            if ($femaleColumn) {
                $hash = ContentHash::hash($content->{$femaleColumn});
    
                // set hash if no hash already set. If the female name is the same
                // as the male name then the hash would be the same and the content id would be as well.
                if (empty($data[$hash])) {
                    $data[$hash] = $content->ID;
                }
            }
        }
        
        Redis::Cache(true)->set("character_{$contentName}", $data, self::REDIS_DURATION);
        $this->keys[] = $contentName;
    }
    
    /**
     * Cache the EXP per level
     */
    private function populateParamGrow()
    {
        $this->io->text(__METHOD__);
        
        $data = [];
        foreach (Redis::Cache(true)->get("ids_ParamGrow") as $id) {
            $content = Redis::Cache(true)->get("xiv_ParamGrow_{$id}");
    
            // don't care about zero exp stuff
            if ($content->ExpToNext == 0) {
                break;
            }
            
            $data[$content->ID] = $content->ExpToNext;
        }
        
        Redis::Cache(true)->set("character_ParamGrow", $data, self::REDIS_DURATION);
        $this->keys[] = 'ParamGrow';
    }
    
    /**
     * Cache the Materia names
     */
    private function populateMateria()
    {
        $this->io->text(__METHOD__);
        
        $data = [];
        foreach (Redis::Cache(true)->get("ids_Item") as $id) {
            $content = Redis::Cache(true)->get("xiv_Item_{$id}");
            
            // if it's a material item
            if (isset($content->ItemUICategory->ID) && $content->ItemUICategory->ID == 58) {
                $hash = ContentHash::hash($content->Name_en);
                $data[$hash] = $content->ID;
            }
        }
        
        Redis::Cache(true)->set("character_Materia", $data, self::REDIS_DURATION);
        $this->keys[] = 'Materia';
    }
    
    /**
     * Cache equipment items
     */
    private function populateItems()
    {
        $this->io->text(__METHOD__);
    
        $data = [];
        foreach (Redis::Cache(true)->get("ids_Item") as $id) {
            $content = Redis::Cache(true)->get("xiv_Item_{$id}");
    
            // only stuff that has a class/job category
            if (isset($content->ClassJobCategory->ID)) {
                $hash = ContentHash::hash($content->Name_en);
                $data[$hash] = $content->ID;
            }
        }
    
        Redis::Cache(true)->set("character_Equipment", $data, self::REDIS_DURATION);
        $this->keys[] = 'Equipment';
    }
    
    /**
     * Cache dyes
     */
    private function populateDyes()
    {
        $this->io->text(__METHOD__);
    
        $data = [];
        foreach (Redis::Cache(true)->get("ids_Item") as $id) {
            $content = Redis::Cache(true)->get("xiv_Item_{$id}");
            
            // if it's a material item
            if (isset($content->ItemUICategory->ID) && $content->ItemUICategory->ID == 55) {
                $hash = ContentHash::hash($content->Name_en);
                $data[$hash] = $content->ID;
            }
        }
    
        Redis::Cache(true)->set("character_Dye", $data, self::REDIS_DURATION);
        $this->keys[] = 'Dye';
    }
}
