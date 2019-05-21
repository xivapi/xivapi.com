<?php

namespace App\Service\Maps;

use App\Entity\MapPosition;
use App\Entity\MemoryData;
use App\Repository\MapPositionRepository;
use App\Repository\MemoryDataRepository;
use Doctrine\ORM\EntityManagerInterface;

class Mappy
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var MapPositionRepository */
    private $repository;
    /** @var MemoryDataRepository */
    private $repositoryMemory;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em               = $em;
        $this->repository       = $em->getRepository(MapPosition::class);
        $this->repositoryMemory = $em->getRepository(MemoryData::class);
    }
    
    public function getMapPositionRepository()
    {
        return $this->repository;
    }
    
    public function getMemoryDataRepository()
    {
        return $this->repositoryMemory;
    }
    
    /**
     * Save some positions
     */
    public function save(array $positions)
    {
        // remove some entries
        foreach ($positions as $i => $pos) {
            if ($pos->MapID == 0) {
                unset($positions[$i]);
            }
        }

        $positions = array_values($positions);

        if (empty($positions)) {
            return;
        }


        // Step 1
        // - Delete duplicate gathering entities, they will be added in step 2
        foreach ($positions as $i => $pos) {
            if ($pos->Type == 'Gathering') {
                $hash = sha1("{$pos->ENpcResidentID},{$pos->Name},{$pos->MapID}");
                
                // delete existing gathering entries with this ID
                $entries = $this->repository->findBy([
                    'ENpcResidentID' => $pos->ENpcResidentID,
                    'Type'           => $pos->Type,
                    'MapID'          => $pos->MapID,
                ]);
        
                if ($entries) {
                    /** @var MapPosition $entry */
                    foreach ($entries as $entry) {
                        // can ignore if hash is the same
                        if ($entry->getHash() != $hash) {
                            $this->em->remove($entry);
                        }
                    }
                    
                    $this->em->flush();
                }
            }
        }
        
        // Step 2
        foreach ($positions as $i => $pos) {
            if ($pos->Type === 'Gathering') {
                // handle gathering differently since it's position is static.
                $hash = sha1("{$pos->ENpcResidentID},{$pos->Name},{$pos->MapID}");
            } else {
                $hash = $this->getPositionHash($pos);
            }

            // skip duplicates
            if ($this->repository->findBy(['Hash' => $hash])) {
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
            $yPos,
        ]));
    }
}
