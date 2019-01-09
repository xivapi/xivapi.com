<?php

namespace App\Service\Companion;

use App\Service\Content\GameData;
use App\Service\Redis\Cache;

trait CompanionEnrich
{
    /** @var GameData */
    protected $game;
    /** @var Cache */
    protected $cache;
    
    public function __construct(GameData $game, Cache $cache)
    {
        $this->game = $game;
        $this->cache = $cache;
    }
    
    /**
     * Get better item info
     */
    protected function getEnrichedItem($itemId): array
    {
        $item = $this->game->one('Item', $itemId);
    
        return [
            'ID'        => $item->ID,
            'Icon'      => $item->Icon,
            'Rarity'    => $item->Rarity,
            'Name_en'   => $item->Name_en,
            'Name_fr'   => $item->Name_fr,
            'Name_de'   => $item->Name_de,
            'Name_ja'   => $item->Name_ja,
        ];
    }
    
    /**
     * Get better town info
     */
    protected function getEnrichedTown($townId)
    {
        $town = $this->cache->get("xiv_Town_{$townId}");
        unset($town->GameContentLinks);
        unset($town->IconID);
        return $town;
    }
    
    /**
     * Get better materia data
     */
    protected function getEnrichedMateria(array $materia): array
    {
        $arr = [];
        foreach ($materia as $mat) {
            $mat->grade = (int)$mat->grade;

            $row   = $this->cache->get("xiv_Materia_{$mat->key}");
            $item  = $row->{"Item{$mat->grade}"};
            $arr[] = $this->getEnrichedItem($item->ID);
        }
        
        return $arr;
    }
}
