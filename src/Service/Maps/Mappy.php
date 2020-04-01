<?php

namespace App\Service\Maps;

use App\Entity\MapPosition;
use App\Repository\MapPositionRepository;
use Doctrine\ORM\EntityManagerInterface;

class Mappy
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var MapPositionRepository */
    private $repository;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em               = $em;
        $this->repository       = $em->getRepository(MapPosition::class);
    }
    
    public function getMapPositionRepository()
    {
        return $this->repository;
    }

    public function getByMap(int $mapId) 
    {
        return $this->repository->findBy(['MapID' => $mapId]);
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
        
        // Step 2
        foreach ($positions as $i => $pos) {
            $hash = $this->getPositionHash($pos);
            
            $existingObj = $this->repository->findOneBy(['Hash' => $hash]);

            if(isset($existingObj)) {
                continue;
            }

            $obj = new MapPosition();

            $obj->setHash($hash)
                ->setType($pos->Type)
                ->setBNpcNameID($pos->BNpcNameID)
                ->setBNpcBaseID($pos->BNpcBaseID)
                ->setFateID($pos->FateID)
                ->setNodeID($pos->NodeID)
                ->setType($pos->Type)
                ->setMapID($pos->MapID)
                ->setMapTerritoryId($pos->MapTerritoryId)
                ->setPlaceNameId($pos->PlaceNameId)
                ->setCoordinateX($pos->CoordinateX)
                ->setCoordinateY($pos->CoordinateY)
                ->setCoordinateZ($pos->CoordinateZ)
                ->setPosX($pos->PosX)
                ->setPosY($pos->PosY)
                ->setPosZ($pos->PosZ)
                ->setPixelX($pos->PixelX)
                ->setPixelY($pos->PixelY);

            try {
                $this->em->persist($obj);
                $this->em->flush();
            } catch (\Exception $e) {
                // ignore
                return $e->getMessage();
            }

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

        $xPos = explode('.', $pos->PosX);
        $xPos[1] = $decimalToSpacer[$xPos[1][0] ?? 0];

        $xPos = implode('', $xPos);
        $yPos = explode('.', $pos->PosY);

        $yPos[1] = $decimalToSpacer[$yPos[1][0] ?? 0];
        $yPos = implode('', $yPos);
        
        return sha1(implode('',[
            $pos->Index,
            $pos->NodeID,
            $pos->BNpcNameID,
            $pos->BNpcBaseID,
            $pos->Type,
            $xPos,
            $yPos,
        ]));
    }
}
