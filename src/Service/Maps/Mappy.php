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

    public function deleteEntry(string $id)
    {
        $entry = $this->repository->findOneBy(['ID' => $id]);
        $this->em->remove($entry);
        return $this->em->flush();
    }

    public function deleteMap(int $mapId)
    {
        $entries = $this->repository->findBy(['MapID' => $mapId]);
        foreach ($entries as $entry) {
            $this->em->remove($entry);
        }
        $this->em->flush();
        return count($entries);
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

            if (isset($existingObj)) {
                continue;
            }

            $obj = new MapPosition();

            try {
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
                    $this->em->persist($obj);
                    $this->em->flush();
                    $saved++;
            } catch (\Exception $e) {
                // ignore
                return 0;
            }
        }
        return $saved;
    }

    private function getPositionHash($pos)
    {
        $xPos = 1;
        $yPos = 1;

        $xPos = explode('.', $pos->PosX)[0];
        $yPos = explode('.', $pos->PosY)[0];

        return sha1(implode('', [
            $pos->NodeID,
            $pos->BNpcNameID,
            $pos->BNpcBaseID,
            $pos->Type,
            $xPos,
            $yPos,
        ]));
    }
}
