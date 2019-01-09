<?php

namespace App\Service\Maps;

use App\Entity\MapPosition;
use App\Entity\MemoryData;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;

class Mappy
{
    const KEYS = [
        'f7fe6d102725423282c44b8c',
        'eb6f10d047a941c4b313b8c8',
        'fdb183fb5b8d4483a7a46ee9',
        '63cc0045d7e847149c3f',
        '683f80c69a48472ca94abcc7',
        '963bac737d3c4533a7691bb4',
        '21931267ef334933ac56a2a2',
        '16a5e5e9edf24c95b3489657',
        'dec187b1a95f4064aff6a723'
    ];
    
    /** @var Cache */
    private $cache;
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em, Cache $cache)
    {
        $this->cache = $cache;
        $this->em = $em;
    }

    public function save($positions): bool
    {
        foreach ($positions as $i => $pos) {
            $hash = $this->getPositionHash($pos);
            
            // skip dupez
            if ($this->em->getRepository(MapPosition::class)->findBy(['Hash' => $hash])) {
                continue;
            }

            $obj = new MapPosition();
            $obj->setHash($hash)
                ->setContentIndex($pos->Index)
                ->setENpcResidentID($pos->ENpcResidentID)
                ->setBNpcNameID($pos->BNpcNameID)
                ->setBNpcBaseID($pos->BNpcBaseID)
                ->setName($pos->Name)
                ->setType($pos->Type)
                ->setMapID($pos->MapID)
                ->setMapIndex($pos->MapIndex)
                ->setMapTerritoryId($pos->MapTerritoryId)
                ->setPlaceNameId($pos->PlaceNameId)
                ->setCoordinateX($pos->CoordinateX)
                ->setCoordinateY($pos->CoordinateY)
                ->setCoordinateZ($pos->CoordinateZ)
                ->setPosX($pos->PosX)
                ->setPosY($pos->PosY)
                ->setPixelX($pos->PixelX)
                ->setPixelY($pos->PixelY);
            
            $mem = new MemoryData();
            $mem->setHash($hash)
                ->setContentIndex($pos->Index)
                ->setENpcResidentID($pos->ENpcResidentID)
                ->setBNpcNameID($pos->BNpcNameID)
                ->setBNpcBaseID($pos->BNpcBaseID)
                ->setTypeID($pos->TypeID)
                ->setRace($pos->Race)
                ->setHPMax($pos->HPMax)
                ->setMPMax($pos->MPMax)
                ->setJobID($pos->JobID)
                ->setLevel($pos->Level)
                ->setFateID(0)
                ->setEventObjectTypeID($pos->EventObjectTypeID)
                ->setGatheringInvisible($pos->GatheringInvisible)
                ->setGatheringStatus($pos->GatheringStatus)
                ->setHitBoxRadius($pos->HitBoxRadius)
                ->setIsGM($pos->IsGM);
            
            $this->em->persist($obj);
            $this->em->persist($mem);
            $this->em->flush();
        }
        
        return true;
    }
    
    private function getPositionHash($pos)
    {
        // this makes sure positions are spaced around that are close to each other
        $decimalToSpacer = [
            0 => 0,
            1 => 0,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
            6 => 3,
            7 => 3,
            8 => 4,
            9 => 4,
        ];
        
        $xPos = 1;
        $yPos = 1;
        
        if ($pos->Index == 'BNPC') {
            $xPos = explode('.', $pos->PosX);
            $xPos[1] = $decimalToSpacer[$xPos[1][0] ?? 0];
    
            $xPos = implode('', $xPos);
            $yPos = explode('.', $pos->PosY);
    
            $yPos[1] = $decimalToSpacer[$yPos[1][0] ?? 0];
            $yPos = implode('', $yPos);
        }
        
        return sha1(implode('',[
            $pos->Index,
            $pos->ENpcResidentID,
            $pos->BNpcNameID,
            $pos->BNpcBaseID,
            $pos->Type,
            $xPos,
            $yPos
        ]));
    }
}
