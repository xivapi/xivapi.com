<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\FreeCompany;
use App\Entity\PvPTeam;
use App\Service\Content\LodestoneData;
use Doctrine\ORM\EntityManagerInterface;

class CharacterQueue
{
    use QueueTrait;

    /**
     * What method to call on the Lodestone Parser API
     */
    const METHOD = LodestoneApi::GET_CHARACTER;

    /**
     * Get entity from database, if it doesn't exist, make one.
     */
    protected static function getEntity(EntityManagerInterface $em, $lodestoneId)
    {
        return $em->getRepository(Character::class)->find($lodestoneId) ?: new Character($lodestoneId);
    }

    /**
     * Handle response specific to this queue
     */
    protected static function handle(EntityManagerInterface $em, Character $character, $lodestoneId, $data): void
    {
        $freeCompanyId = $data->FreeCompanyId ?? false;
        $pvpTeamId     = $data->PvPTeamId ?? false;
        
        // if the character is newly added, try add their Free Company
        if ($character->isAdding()
            && $freeCompanyId
            && $em->getRepository(FreeCompany::class)->find($data->FreeCompanyId) === null
        ) {
            self::save($em, new FreeCompany($data->FreeCompanyId));
            FreeCompanyQueue::request($data->FreeCompanyId, 'free_company_add');
        }
    
        // if the character is newly added, try add their pvp team
        if ($character->isAdding()
            && $pvpTeamId
            && $em->getRepository(PvPTeam::class)->find($data->PvPTeamId) === null
        ) {
            self::save($em, new PvPTeam($data->PvPTeamId));
            PvPTeamQueue::request($data->PvPTeamId, 'pvp_team_add');
        }
        
        LodestoneData::save('character', 'data', $lodestoneId, $data);
        self::save($em, $character->setStateCached());
    }
}
