<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(name="memory_data")
 * @ORM\Entity(repositoryClass="App\Repository\MemoryDataRepository")
 */
class MemoryData
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $ID;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $Hash;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $Added;
    /**
     * @var string
     * @ORM\Column(type="string", length=4)
     */
    private $ContentIndex;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $ENpcResidentID;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $BNpcNameID;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $BNpcBaseID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $TypeID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=2)
     */
    private $Race;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $HPMax;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $MPMax;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $JobID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $Level;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $FateID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $EventObjectTypeID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $GatheringInvisible;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $GatheringStatus;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $HitBoxRadius;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $IsGM;
    
    public function __construct()
    {
        $this->ID = Uuid::uuid4();
        $this->Added = time();
    }
    
    public function toArray()
    {
        return [
            'Hash'              => $this->Hash,
            'ContentIndex'      => $this->ContentIndex,
            'ENpcResidentID'    => $this->ENpcResidentID,
            'BNpcNameID'        => $this->BNpcNameID,
            'BNpcBaseID'        => $this->BNpcBaseID,
            'TypeID'            => $this->TypeID,
            'Race'              => $this->Race,
            'HPMax'             => $this->HPMax,
            'MPMax'             => $this->MPMax,
            'JobID'             => $this->JobID,
            'Level'             => $this->Level,
            'FateID'            => $this->FateID,
            'EventObjectTypeID' => $this->EventObjectTypeID,
            'GatheringInvisible'=> $this->GatheringInvisible,
            'GatheringStatus'   => $this->GatheringStatus,
            'HitBoxRadius'      => $this->HitBoxRadius,
            'IsGM'              => $this->IsGM,
        ];
    }
    
    public function getID(): string
    {
        return $this->ID;
    }
    
    public function setID(string $ID)
    {
        $this->ID = $ID;
        return $this;
    }
    
    public function getHash(): string
    {
        return $this->Hash;
    }
    
    public function setHash(string $Hash)
    {
        $this->Hash = $Hash;
        return $this;
    }
    
    public function getAdded(): int
    {
        return $this->Added;
    }
    
    public function setAdded(int $Added)
    {
        $this->Added = $Added;
        return $this;
    }
    
    public function getContentIndex(): string
    {
        return $this->ContentIndex;
    }
    
    public function setContentIndex(string $ContentIndex)
    {
        $this->ContentIndex = $ContentIndex;
        return $this;
    }
    
    public function getENpcResidentID(): string
    {
        return $this->ENpcResidentID;
    }
    
    public function setENpcResidentID(string $ENpcResidentID)
    {
        $this->ENpcResidentID = $ENpcResidentID;
        return $this;
    }
    
    public function getBNpcNameID(): string
    {
        return $this->BNpcNameID;
    }
    
    public function setBNpcNameID(string $BNpcNameID)
    {
        $this->BNpcNameID = $BNpcNameID;
        return $this;
    }
    
    public function getBNpcBaseID(): string
    {
        return $this->BNpcBaseID;
    }
    
    public function setBNpcBaseID(string $BNpcBaseID)
    {
        $this->BNpcBaseID = $BNpcBaseID;
        return $this;
    }
    
    public function getTypeID(): int
    {
        return $this->TypeID;
    }
    
    public function setTypeID(int $TypeID)
    {
        $this->TypeID = $TypeID;
        return $this;
    }
    
    public function getRace(): int
    {
        return $this->Race;
    }
    
    public function setRace(int $Race)
    {
        $this->Race = $Race;
        return $this;
    }
    
    public function getHPMax(): int
    {
        return $this->HPMax;
    }
    
    public function setHPMax(int $HPMax)
    {
        $this->HPMax = $HPMax;
        return $this;
    }
    
    public function getMPMax(): int
    {
        return $this->MPMax;
    }
    
    public function setMPMax(int $MPMax)
    {
        $this->MPMax = $MPMax;
        return $this;
    }
    
    public function getJobID(): int
    {
        return $this->JobID;
    }
    
    public function setJobID(int $JobID)
    {
        $this->JobID = $JobID;
        return $this;
    }
    
    public function getLevel(): int
    {
        return $this->Level;
    }
    
    public function setLevel(int $Level)
    {
        $this->Level = $Level;
        return $this;
    }
    
    public function getFateID(): int
    {
        return $this->FateID;
    }
    
    public function setFateID(int $FateID)
    {
        $this->FateID = $FateID;
        return $this;
    }
    
    public function getEventObjectTypeID(): int
    {
        return $this->EventObjectTypeID;
    }
    
    public function setEventObjectTypeID(int $EventObjectTypeID)
    {
        $this->EventObjectTypeID = $EventObjectTypeID;
        return $this;
    }
    
    public function getGatheringInvisible(): int
    {
        return $this->GatheringInvisible;
    }
    
    public function setGatheringInvisible(int $GatheringInvisible)
    {
        $this->GatheringInvisible = $GatheringInvisible;
        return $this;
    }
    
    public function getGatheringStatus(): int
    {
        return $this->GatheringStatus;
    }
    
    public function setGatheringStatus(int $GatheringStatus)
    {
        $this->GatheringStatus = $GatheringStatus;
        return $this;
    }
    
    public function getHitBoxRadius(): int
    {
        return $this->HitBoxRadius;
    }
    
    public function setHitBoxRadius(int $HitBoxRadius)
    {
        $this->HitBoxRadius = $HitBoxRadius;
        return $this;
    }
    
    public function isGM(): bool
    {
        return $this->IsGM;
    }
    
    public function setIsGM(bool $IsGM)
    {
        $this->IsGM = $IsGM;
        return $this;
    }
}
