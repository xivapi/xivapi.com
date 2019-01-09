<?php

namespace App\Service\LodestoneQueue;

use App\Entity\CharacterAchievements;
use App\Entity\Entity;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class CharacterAchievementQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_CHARACTER_ACHIEVEMENTS_FULL;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(CharacterAchievements::class)->find($lodestoneId) ?: new CharacterAchievements($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    protected static function handle(EntityManagerInterface $em, CharacterAchievements $ca, $lodestoneId, $data): void
    {
        $achievements = (Object)[
            'ParseDate' => time(),
            'Points'    => 0,
            'List'      => [],
        ];

        foreach ($data->Achievements as $kind => $achieves) {
            $achievements->Points    += $achieves->PointsObtained;
            $achievements->ParseDate = $achieves->ParseDate;

            foreach ($achieves->Achievements as $achievement) {
                $achievements->List[] = [
                    'ID'   => $achievement->ID,
                    'Date' => $achievement->ObtainedTimestamp,
                ];
            }
        }

        LodestoneData::save('character', 'achievements', $lodestoneId, $achievements);
        self::save($em, $ca->setStateCached());
    }
}
