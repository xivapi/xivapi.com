<?php

namespace App\Service\DataCustom\Pre;

use App\Service\Data\FileSystem;
use App\Service\DataCustom\Icons;

class PreHandler
{
    const CUSTOMISE = [
        // Icons
        [ 'BNpcName', Icons::class, 'setBNpcNameIcon' ],
        [ 'ENpcResident', Icons::class, 'setENpcResidentIcon' ],
        [ 'Leve', Icons::class, 'setLeveIcon' ],
        [ 'PlaceName', Icons::class, 'setPlaceNameIcon' ],
        [ 'Title', Icons::class, 'setTitleIcon' ],
        [ 'ClassJob', Icons::class, 'setClassJobIcon' ],
        [ 'Companion', Icons::class, 'setCompanionIcon' ],
        [ 'Mount', Icons::class, 'setMountIcon' ],
        
        // Maps
        [ 'Map', Icons::class, 'setMapFilename' ],
    ];
    
    public static function CustomDataConverter()
    {
        foreach (self::CUSTOMISE as $customize) {
            [$contentName, $class, $function] = $customize;
    
            // load content for this piece of content
            $contentData = FileSystem::load($contentName, 'json');
            
            foreach ($contentData as $content) {
                $class::{$function}($content);
            }
     
            FileSystem::save($contentName, 'json', $contentData);
        }
    }
}
