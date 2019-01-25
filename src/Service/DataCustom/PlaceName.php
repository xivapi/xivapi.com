<?php

namespace App\Service\DataCustom;

use App\Service\Content\ManualHelper;

class PlaceName extends ManualHelper
{
    const PRIORITY = 20;
    
    public function handle()
    {
        $this->linkMapsToPlaceNames();
    }
    
    private function linkMapsToPlaceNames()
    {
        // reset the map state of all PlaceNames, this is so if the script is ran
        // multiple times it doesn't append on duplicates, also all PlaceNames
        // get this field, even if no maps.
        foreach ($this->redis->get("ids_PlaceName") as $id) {
            $placename = $this->redis->get("xiv_PlaceName_{$id}");
            $placename->Maps = [];
            $this->redis->set("xiv_PlaceName_{$id}", $placename, self::REDIS_DURATION);
        }
        
        foreach ($this->redis->get("ids_Map") as $id) {
            $map = $this->redis->get("xiv_Map_{$id}");
            
            // remove content links, to much data for a nested entity
            unset($map->GameContentLinks);
            
            //$map = Arrays::minification($map);
            //$map = json_decode(json_encode($map));
            
            $this->linkMapsToPlaceNameHandler($map, 'PlaceName');
            $this->linkMapsToPlaceNameHandler($map, 'PlaceNameRegion');
            $this->linkMapsToPlaceNameHandler($map, 'PlaceNameSub');
        }
    }
    
    private function linkMapsToPlaceNameHandler($map, $field)
    {
        $id = $map->{$field}->ID ?? 0;
        
        if ($id == 0) {
            return;
        }
    
        $placename = $this->redis->get("xiv_PlaceName_{$id}");
        
        // append on this map and remove any junk
        $placename->Maps[] = $map;
        $placename->Maps = array_filter($placename->Maps);
        
        // save
        $this->redis->set("xiv_PlaceName_{$id}", $placename, self::REDIS_DURATION);
    }
}
