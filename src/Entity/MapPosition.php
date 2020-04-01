<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="map_positions",
 *     indexes={
 *          @ORM\Index(name="map_id", columns={"map_id"}),
 *          @ORM\Index(name="map_territory_id", columns={"map_territory_id"}),
 *          @ORM\Index(name="node_id", columns={"node_id"}),
 *          @ORM\Index(name="place_name_id", columns={"place_name_id"}),
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
    public $ID;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    public $Hash;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    public $Added;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    public $BNpcNameID;
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    public $BNpcBaseID;
    /**
     * Possible values: Node, BNPC
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    public $Type;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    public $MapID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    public $FateID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    public $NodeID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    public $MapTerritoryID;
    /**
     * @var int
     * @ORM\Column(type="integer", length=12)
     */
    public $PlaceNameID;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $CoordinateX;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $CoordinateY;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $CoordinateZ;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $PosX;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $PosY;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    public $PosZ;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    public $PixelX;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    public $PixelY;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    public $HP;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    public $Level;
    
    public function __construct()
    {
        $this->ID = Uuid::uuid4();
        $this->Added = time();
    }
    
    public function toArray(): array
    {
        return [
            'Hash'              => $this->Hash,
            'NodeID'            => $this->NodeID,
            'BNpcNameID'        => $this->BNpcNameID,
            'BNpcBaseID'        => $this->BNpcBaseID,
            'Type'              => $this->Type,
            'MapID'             => $this->MapID,
            'FateID'            => $this->FateID,
            'HP'                => $this->HP,
            'Level'             => $this->Level,
            'MapTerritoryID'    => $this->MapTerritoryID,
            'PlaceNameID'       => $this->PlaceNameID,
            'CoordinateX'       => $this->CoordinateX,
            'CoordinateY'       => $this->CoordinateY,
            'CoordinateZ'       => $this->CoordinateZ,
            'PosX'              => $this->PosX,
            'PosY'              => $this->PosY,
            'PosZ'              => $this->PosZ,
            'PixelX'            => $this->PixelX,
            'PixelY'            => $this->PixelY
        ];
    }

    /**
     * Get the value of ID
     *
     * @return  string
     */ 
    public function getID()
    {
        return $this->ID;
    }

    /**
     * Set the value of ID
     *
     * @param  string  $ID
     *
     * @return  self
     */ 
    public function setID(string $ID)
    {
        $this->ID = $ID;

        return $this;
    }

    /**
     * Get the value of Hash
     *
     * @return  string
     */ 
    public function getHash()
    {
        return $this->Hash;
    }

    /**
     * Set the value of Hash
     *
     * @param  string  $Hash
     *
     * @return  self
     */ 
    public function setHash(string $Hash)
    {
        $this->Hash = $Hash;

        return $this;
    }

    /**
     * Get the value of Added
     *
     * @return  int
     */ 
    public function getAdded()
    {
        return $this->Added;
    }

    /**
     * Set the value of Added
     *
     * @param  int  $Added
     *
     * @return  self
     */ 
    public function setAdded(int $Added)
    {
        $this->Added = $Added;

        return $this;
    }

    /**
     * Get the value of BNpcNameID
     *
     * @return  string
     */ 
    public function getBNpcNameID()
    {
        return $this->BNpcNameID;
    }

    /**
     * Set the value of BNpcNameID
     *
     * @param  string  $BNpcNameID
     *
     * @return  self
     */ 
    public function setBNpcNameID(string $BNpcNameID)
    {
        $this->BNpcNameID = $BNpcNameID;

        return $this;
    }

    /**
     * Get the value of BNpcBaseID
     *
     * @return  string
     */ 
    public function getBNpcBaseID()
    {
        return $this->BNpcBaseID;
    }

    /**
     * Set the value of BNpcBaseID
     *
     * @param  string  $BNpcBaseID
     *
     * @return  self
     */ 
    public function setBNpcBaseID(string $BNpcBaseID)
    {
        $this->BNpcBaseID = $BNpcBaseID;

        return $this;
    }

    /**
     * Get the value of Type
     *
     * @return  string
     */ 
    public function getType()
    {
        return $this->Type;
    }

    /**
     * Set the value of Type
     *
     * @param  string  $Type
     *
     * @return  self
     */ 
    public function setType(string $Type)
    {
        $this->Type = $Type;

        return $this;
    }

    /**
     * Get the value of MapID
     *
     * @return  int
     */ 
    public function getMapID()
    {
        return $this->MapID;
    }

    /**
     * Set the value of MapID
     *
     * @param  int  $MapID
     *
     * @return  self
     */ 
    public function setMapID(int $MapID)
    {
        $this->MapID = $MapID;

        return $this;
    }

    /**
     * Get the value of FateID
     *
     * @return  int
     */ 
    public function getFateID()
    {
        return $this->FateID;
    }

    /**
     * Set the value of FateID
     *
     * @param  int  $FateID
     *
     * @return  self
     */ 
    public function setFateID(int $FateID)
    {
        $this->FateID = $FateID;

        return $this;
    }

    /**
     * Get the value of MapTerritoryID
     *
     * @return  int
     */ 
    public function getMapTerritoryID()
    {
        return $this->MapTerritoryID;
    }

    /**
     * Set the value of MapTerritoryID
     *
     * @param  int  $MapTerritoryID
     *
     * @return  self
     */ 
    public function setMapTerritoryID(int $MapTerritoryID)
    {
        $this->MapTerritoryID = $MapTerritoryID;

        return $this;
    }

    /**
     * Get the value of PlaceNameID
     *
     * @return  int
     */ 
    public function getPlaceNameID()
    {
        return $this->PlaceNameID;
    }

    /**
     * Set the value of PlaceNameID
     *
     * @param  int  $PlaceNameID
     *
     * @return  self
     */ 
    public function setPlaceNameID(int $PlaceNameID)
    {
        $this->PlaceNameID = $PlaceNameID;

        return $this;
    }

    /**
     * Get the value of CoordinateX
     *
     * @return  float
     */ 
    public function getCoordinateX()
    {
        return $this->CoordinateX;
    }

    /**
     * Set the value of CoordinateX
     *
     * @param  float  $CoordinateX
     *
     * @return  self
     */ 
    public function setCoordinateX(float $CoordinateX)
    {
        $this->CoordinateX = $CoordinateX;

        return $this;
    }

    /**
     * Get the value of CoordinateY
     *
     * @return  float
     */ 
    public function getCoordinateY()
    {
        return $this->CoordinateY;
    }

    /**
     * Set the value of CoordinateY
     *
     * @param  float  $CoordinateY
     *
     * @return  self
     */ 
    public function setCoordinateY(float $CoordinateY)
    {
        $this->CoordinateY = $CoordinateY;

        return $this;
    }

    /**
     * Get the value of CoordinateZ
     *
     * @return  float
     */ 
    public function getCoordinateZ()
    {
        return $this->CoordinateZ;
    }

    /**
     * Set the value of CoordinateZ
     *
     * @param  float  $CoordinateZ
     *
     * @return  self
     */ 
    public function setCoordinateZ(float $CoordinateZ)
    {
        $this->CoordinateZ = $CoordinateZ;

        return $this;
    }

    /**
     * Get the value of PosX
     *
     * @return  float
     */ 
    public function getPosX()
    {
        return $this->PosX;
    }

    /**
     * Set the value of PosX
     *
     * @param  float  $PosX
     *
     * @return  self
     */ 
    public function setPosX(float $PosX)
    {
        $this->PosX = $PosX;

        return $this;
    }

    /**
     * Get the value of PosY
     *
     * @return  float
     */ 
    public function getPosY()
    {
        return $this->PosY;
    }

    /**
     * Set the value of PosY
     *
     * @param  float  $PosY
     *
     * @return  self
     */ 
    public function setPosY(float $PosY)
    {
        $this->PosY = $PosY;

        return $this;
    }

    /**
     * Get the value of PosZ
     *
     * @return  float
     */ 
    public function getPosZ()
    {
        return $this->PosZ;
    }

    /**
     * Set the value of PosZ
     *
     * @param  float  $PosZ
     *
     * @return  self
     */ 
    public function setPosZ(float $PosZ)
    {
        $this->PosZ = $PosZ;

        return $this;
    }

    /**
     * Get the value of PixelX
     *
     * @return  int
     */ 
    public function getPixelX()
    {
        return $this->PixelX;
    }

    /**
     * Set the value of PixelX
     *
     * @param  int  $PixelX
     *
     * @return  self
     */ 
    public function setPixelX(int $PixelX)
    {
        $this->PixelX = $PixelX;

        return $this;
    }

    /**
     * Get the value of PixelY
     *
     * @return  int
     */ 
    public function getPixelY()
    {
        return $this->PixelY;
    }

    /**
     * Set the value of PixelY
     *
     * @param  int  $PixelY
     *
     * @return  self
     */ 
    public function setPixelY(int $PixelY)
    {
        $this->PixelY = $PixelY;

        return $this;
    }

    /**
     * Get the value of HP
     *
     * @return  int
     */ 
    public function getHP()
    {
        return $this->HP;
    }

    /**
     * Set the value of HP
     *
     * @param  int  $HP
     *
     * @return  self
     */ 
    public function setHP(int $HP)
    {
        $this->HP = $HP;

        return $this;
    }

    /**
     * Get the value of Level
     *
     * @return  int
     */ 
    public function getLevel()
    {
        return $this->Level;
    }

    /**
     * Set the value of Level
     *
     * @param  int  $Level
     *
     * @return  self
     */ 
    public function setLevel(int $Level)
    {
        $this->Level = $Level;

        return $this;
    }

    /**
     * Get the value of NodeID
     *
     * @return  int
     */ 
    public function getNodeID()
    {
        return $this->NodeID;
    }

    /**
     * Set the value of NodeID
     *
     * @param  int  $NodeID
     *
     * @return  self
     */ 
    public function setNodeID(int $NodeID)
    {
        $this->NodeID = $NodeID;

        return $this;
    }
}
