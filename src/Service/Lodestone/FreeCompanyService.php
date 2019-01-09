<?php

namespace App\Service\Lodestone;

use App\Entity\FreeCompany;
use App\Service\Content\LodestoneData;
use App\Service\LodestoneQueue\FreeCompanyQueue;
use App\Service\Service;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class FreeCompanyService extends Service
{
    /**
     * Get a Free Company
     */
    public function get($lodestoneId): \stdClass
    {
        if ($lodestoneId < 0 || preg_match("/[a-z]/i", $lodestoneId) || strlen($lodestoneId) < 15 || strlen($lodestoneId) > 20) {
            throw new NotAcceptableHttpException('Invalid lodestone ID: '. $lodestoneId);
        }
        
        /** @var FreeCompany $ent */
        if ($ent = $this->getRepository(FreeCompany::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->isCached()) {
                $data = LodestoneData::load('freecompany', 'data', $lodestoneId);
            }
            
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }
    
        FreeCompanyQueue::request($lodestoneId, 'free_company_add');
        
        return (Object)[
            'ent'  => new FreeCompany($lodestoneId),
            'data' => null,
        ];
    }
    
    /**
     * Get Free Company members
     */
    public function getMembers($lodestoneId): \stdClass
    {
        /** @var FreeCompany $ent */
        if ($ent = $this->getRepository(FreeCompany::class)->find($lodestoneId)) {
            // if entity is cached, grab the data
            if ($ent->isCached()) {
                $data = LodestoneData::load('freecompany', 'members', $lodestoneId);
            }
        
            return (Object)[
                'ent'  => $ent,
                'data' => $data ?? null,
            ];
        }
    
        return (Object)[
            'ent'  => new FreeCompany($lodestoneId),
            'data' => null,
        ];
    }
}
