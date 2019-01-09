<?php

namespace App\Service\LodestoneQueue;

use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class CharacterFriendQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_CHARACTER_FRIENDS_FULL;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(CharacterFriends::class)->find($lodestoneId) ?: new CharacterFriends($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    protected static function handle(EntityManagerInterface $em, CharacterFriends $cf, $lodestoneId, $data): void
    {
        LodestoneData::save('character', 'friends', $lodestoneId, $data->Members);
        self::save($em, $cf->setStateCached());
    }
}
