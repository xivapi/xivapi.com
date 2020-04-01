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

    public function getFullData()
    {
        return $this->repository->findAll();
    }
    
    /**
     * Save some positions
     */
    public function save(array $positions)
    {
        if (empty($positions)) {
            return;
        }

        $saved = 0;
        
        // Step 2
        foreach ($positions as $pos) {
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
                ->setMapTerritoryId($pos->MapTerritoryID)
                ->setPlaceNameId($pos->PlaceNameID)
                ->setCoordinateX($pos->CoordinateX)
                ->setCoordinateY($pos->CoordinateY)
                ->setCoordinateZ($pos->CoordinateZ)
                ->setPosX($pos->PosX)
                ->setPosY($pos->PosY)
                ->setPosZ($pos->PosZ)
                ->setPixelX($pos->PixelX)
                ->setPixelY($pos->PixelY)
                ->setHP($pos->HP)
                ->setLevel($pos->Level);

            try {
                $this->em->persist($obj);
                $this->em->flush();
                $saved++;
            } catch (\Exception $e) {
                // ignore
                return $e->getMessage();
            }

        }
        return $saved;
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
            $pos->NodeID,
            $pos->BNpcNameID,
            $pos->BNpcBaseID,
            $pos->Type,
            $xPos,
            $yPos,
        ]));
    }
}
