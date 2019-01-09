<?php

namespace App\Service\Lodestone;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\CharacterAchievementQueue;
use App\Service\LodestoneQueue\CharacterConverter;
use App\Service\LodestoneQueue\CharacterFriendQueue;
use App\Service\LodestoneQueue\CharacterQueue;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class CharacterService extends Service
{
    /**
     * Get a character; this will add the character if they do not exist
     */
    public function get($lodestoneId, bool $extended = null): \stdClass
    {
        if (!is_numeric($lodestoneId) || $lodestoneId < 0 || preg_match("/[a-z]/i", $lodestoneId) || strlen($lodestoneId) > 16) {
            throw new NotAcceptableHttpException("Invalid character id: {$lodestoneId}");
        }

        /** @var Character $ent */
        if ($ent = $this->getRepository(Character::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->isCached()) {
                $data = LodestoneData::load('character', 'data', $lodestoneId);
                CharacterConverter::handle($data);
                
                if ($extended) {
                    LodestoneData::extendCharacterData($data);
                }
            }
            
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }
    
        CharacterQueue::request($lodestoneId, 'character_add');
        CharacterFriendQueue::request($lodestoneId, 'character_friends_add');
        CharacterAchievementQueue::request($lodestoneId, 'character_achievements_add');
        
        return (Object)[
            'ent'  => new Character($lodestoneId),
            'data' => null,
        ];
    }
    
    /**
     * Get character achievements
     */
    public function getAchievements($lodestoneId, bool $extended = null): \stdClass
    {
        /** @var Character $ent */
        if ($ent = $this->getRepository(CharacterAchievements::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->isCached()) {
                $data = LodestoneData::load('character', 'achievements', $lodestoneId);
                
                if ($extended) {
                    LodestoneData::extendAchievementData($data);
                }
            }
    
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }
    
        return (Object)[
            'ent'  => new CharacterAchievements($lodestoneId),
            'data' => null,
        ];
    }
    
    /**
     * Get character friends
     */
    public function getFriends($lodestoneId): \stdClass
    {
        /** @var Character $ent */
        if ($ent = $this->getRepository(CharacterFriends::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->getState() == Entity::STATE_CACHED) {
                $data = LodestoneData::load('character', 'friends', $lodestoneId);
            }
    
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }
    
        return (Object)[
            'ent'  => new CharacterFriends($lodestoneId),
            'data' => null,
        ];
    }
}
