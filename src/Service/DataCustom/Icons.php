<?php

namespace App\Service\DataCustom;

use App\Service\Data\DataHelper;
use App\Service\Content\ManualHelper;

class Icons extends ManualHelper
{
    const PRIORITY = 20;
    
    public static function setBNpcNameIcon($content)
    {
        $content->Icon = '/c/BNpcName.png';
    }
    
    public static function setENpcResidentIcon($content)
    {
        $content->Icon = '/c/ENpcResident.png';
    }
    
    public static function setLeveIcon($content)
    {
        $content->Icon = '/c/Leve.png';
    }
    
    public static function setPlaceNameIcon($content)
    {
        $content->Icon = '/c/PlaceName.png';
    }
    
    public static function setTitleIcon($content)
    {
        $content->Icon = '/c/Title.png';
    }
    
    public static function setClassJobIcon($content)
    {
        $filename = str_ireplace(' ', null, strtolower($content->Name_en));
        $content->Icon = "/cj/1/{$filename}.png";
    }
    
    public static function setCompanionIcon($content)
    {
        $content->IconSmall = $content->Icon;
    
        $rep = [
            '/004' => '/068',
            '/008' => '/077',
        ];
    
        $content->Icon = str_ireplace(array_keys($rep), $rep, $content->Icon);
    }
    
    public static function setMountIcon($content)
    {
        $content->IconSmall = $content->Icon;
    
        $rep = [
            '/004' => '/068',
            '/008' => '/077',
        ];
    
        $content->Icon = str_ireplace(array_keys($rep), $rep, $content->Icon);
    }
    
    public function setMapFilename($content)
    {
        if (!isset($content->Id_en)) {
            return;
        }
        
        $content->MapFilename = null;
        $content->MapFilenameId = $content->Id_en;
        unset($content->Id_en);
    
        if (!empty($content->MapFilenameId)) {
            [$folder, $layer] = explode('/', $content->MapFilenameId);
            $content->MapFilename = "/m/{$folder}/{$folder}.{$layer}.jpg";
        }
    }
    
    //
    // todo - Change this to work on "pre", Recipes and Quests are tricky ...
    //
    
    public function handle()
    {
        $this->setQuestIcons();
        $this->setRecipeIcon();
    }

    /**
     * Set quest icons
     */
    public function setQuestIcons()
    {
        $this->io->text(__METHOD__);
        foreach ($this->redis->get('ids_Quest') as $id) {
            $key = "xiv_Quest_{$id}";
            $content = $this->redis->get($key);
            
            $content->Banner    = $content->Icon;
            $content->IconID    = 71221;
            $content->Icon      = DataHelper::getImagePath(71221);
            $content->IconSmall = $content->Icon;
            
            // Use journal icon if it exists
            if (isset($content->JournalGenre->IconID) && $content->JournalGenre->IconID) {
                // tweak some journal icons to higher res versions
                $ids = [
                    '61411' => '71221',
                    '61412' => '71201',
                    '61413' => '71222',
                    '61414' => '71281',
                    '61415' => '60552',
                    '61416' => '61436',
        
                    // grand companies
                    '61401' => '62951', // limsa
                    '61402' => '62952', // grid
                    '61403' => '62953', // uldah
                ];
    
                $content->IconID = $ids[$content->JournalGenre->IconID] ?? $content->JournalGenre->IconID;
                $content->Icon = DataHelper::getImagePath($content->IconID);
            }
            
            // if there is a special icon, use that
            if ($content->IconSpecial) {
                $content->IconSmall = $content->Icon;
                $content->Icon      = $content->IconSpecial;
                $content->IconID    = $content->IconSpecialID;
            }
    
            $this->redis->set($key, $content, self::REDIS_DURATION);
        }
    }
    
    /**
     * Add HQ recipe icons
     */
    public function setRecipeIcon()
    {
        $this->io->text(__METHOD__);
        
        foreach ($this->redis->get('ids_Recipe') as $id) {
            $key = "xiv_Recipe_{$id}";
            $content = $this->redis->get($key);
            
            if (!isset($content->ItemResult->ID)) {
                continue;
            }
            
            $resultItem = $this->redis->get("xiv_Item_{$content->ItemResult->ID}");
            $content->Icon = $resultItem->Icon;
            $content->IconID = $resultItem->IconID;
    
            $this->redis->set($key, $content, self::REDIS_DURATION);
        }
    }
}
