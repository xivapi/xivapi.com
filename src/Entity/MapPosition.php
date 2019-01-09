<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(name="map_positions")
 * @ORM\Table(
 *     name="map_positions",
 *     indexes={
 *          @ORM\Index(name="map_id", columns={"map_id"}),
 *          @ORM\Index(name="map_index", columns={"map_index"}),
 *          @ORM\Index(name="map_territory_id", columns={"map_territory_id"}),
 *          @ORM\Index(name="place_name_id", columns={"place_name_id"}),
 *          @ORM\Index(name="content_index", columns={"content_index"}),
 *          @ORM\Index(name="managed", columns={"managed"}),
 *          @ORM\Index(name="added", columns={"added"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MapPositionRepository")
 */
class MapPosition
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
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $Name;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $Type;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $MapID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $MapIndex;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $MapTerritoryID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    private $PlaceNameID;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $CoordinateX;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $CoordinateY;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $CoordinateZ;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $PosX;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $PosY;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $PixelX;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $PixelY;
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $Managed = false;
    
    public function __construct()
    {
        $this->ID = Uuid::uuid4();
        $this->Added = time();
    }
    
    public function toArray(): array
    {
        return [
            'Hash'              => $this->Hash,
            'ContentIndex'      => $this->ContentIndex,
            'ENpcResidentID'    => $this->ENpcResidentID,
            'BNpcNameID'        => $this->BNpcNameID,
            'BNpcBaseID'        => $this->BNpcBaseID,
            'Name'              => $this->Name,
            'Type'              => $this->Type,
            'MapID'             => $this->MapID,
            'MapIndex'          => $this->MapIndex,
            'MapTerritoryID'    => $this->MapTerritoryID,
            'PlaceNameID'       => $this->PlaceNameID,
            'CoordinateX'       => $this->CoordinateX,
            'CoordinateY'       => $this->CoordinateY,
            'CoordinateZ'       => $this->CoordinateZ,
            'PosX'              => $this->PosX,
            'PosY'              => $this->PosY,
            'PixelX'            => $this->PixelX,
            'PixelY'            => $this->PixelY,
            'Managed'           => $this->Managed,
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
    
    public function getName(): string
    {
        return $this->Name;
    }
    
    public function setName(string $Name)
    {
        $this->Name = $Name;
        return $this;
    }
    
    public function getType(): string
    {
        return $this->Type;
    }
    
    public function setType(string $Type)
    {
        $this->Type = $Type;
        return $this;
    }
    
    public function getMapID(): int
    {
        return $this->MapID;
    }
    
    public function setMapID(int $MapID)
    {
        $this->MapID = $MapID;
        return $this;
    }
    
    public function getMapIndex(): int
    {
        return $this->MapIndex;
    }
    
    public function setMapIndex(int $MapIndex)
    {
        $this->MapIndex = $MapIndex;
        return $this;
    }
    
    public function getMapTerritoryID(): int
    {
        return $this->MapTerritoryID;
    }
    
    public function setMapTerritoryID(int $MapTerritoryID)
    {
        $this->MapTerritoryID = $MapTerritoryID;
        return $this;
    }
    
    public function getPlaceNameID(): int
    {
        return $this->PlaceNameID;
    }
    
    public function setPlaceNameID(int $PlaceNameID)
    {
        $this->PlaceNameID = $PlaceNameID;
        return $this;
    }
    
    public function getCoordinateX(): float
    {
        return $this->CoordinateX;
    }
    
    public function setCoordinateX(float $CoordinateX)
    {
        $this->CoordinateX = $CoordinateX;
        return $this;
    }
    
    public function getCoordinateY(): float
    {
        return $this->CoordinateY;
    }
    
    public function setCoordinateY(float $CoordinateY)
    {
        $this->CoordinateY = $CoordinateY;
        return $this;
    }
    
    public function getCoordinateZ(): float
    {
        return $this->CoordinateZ;
    }
    
    public function setCoordinateZ(float $CoordinateZ)
    {
        $this->CoordinateZ = $CoordinateZ;
        return $this;
    }
    
    public function getPosX(): float
    {
        return $this->PosX;
    }
    
    public function setPosX(float $PosX)
    {
        $this->PosX = $PosX;
        return $this;
    }
    
    public function getPosY(): float
    {
        return $this->PosY;
    }
    
    public function setPosY(float $PosY)
    {
        $this->PosY = $PosY;
        return $this;
    }
    
    public function getPixelX(): int
    {
        return $this->PixelX;
    }
    
    public function setPixelX(int $PixelX)
    {
        $this->PixelX = $PixelX;
        return $this;
    }
    
    public function getPixelY(): int
    {
        return $this->PixelY;
    }
    
    public function setPixelY(int $PixelY)
    {
        $this->PixelY = $PixelY;
        return $this;
    }

    public function getManaged()
    {
        return $this->Managed;
    }

    public function setManaged($Managed)
    {
        $this->Managed = $Managed;
        return $this;
    }
}
