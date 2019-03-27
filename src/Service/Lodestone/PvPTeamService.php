<?php

namespace App\Service\Lodestone;

use App\Entity\PvPTeam;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class PvPTeamService extends AbstractService
{
    /**
     * Get a PVP Team
     */
    public function get($lodestoneId)
    {
        if (strlen($lodestoneId) < 35 || strlen($lodestoneId) > 45) {
            throw new NotAcceptableHttpException('Invalid lodestone ID: '. $lodestoneId);
        }
    
        /** @var PvPTeam $ent */
        if ($ent = $this->getRepository(PvPTeam::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->isCached()) {
                $data = LodestoneData::load('pvpteam', 'data', $lodestoneId);
            }
        
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }

        PvPTeamQueue::request($lodestoneId, 'pvp_team_add', true);
    
        return (Object)[
            'ent'  => new PvPTeam($lodestoneId),
            'data' => null,
        ];
    }
}
