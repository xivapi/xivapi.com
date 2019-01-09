<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="map_positions_completed",
 *     indexes={
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="map_id", columns={"map_id"}),
 *          @ORM\Index(name="complete", columns={"complete"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MapCompletionRepository")
 */
class MapCompletion
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $ID;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $Updated;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $MapID;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $Complete = false;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $Notes;
    
    public function __construct()
    {
        $this->ID = Uuid::uuid4();
        $this->Updated = time();
    }
    
    public function getID(): ?string
    {
        return $this->ID;
    }
    
    public function setID(string $ID)
    {
        $this->ID = $ID;
        
        return $this;
    }
    
    public function getUpdated(): ?int
    {
        return $this->Updated;
    }
    
    public function setUpdated(int $Updated)
    {
        $this->Updated = $Updated;
        return $this;
    }
    
    public function getMapID(): ?string
    {
        return $this->MapID;
    }
    
    public function setMapID(string $MapID)
    {
        $this->MapID = $MapID;
        $this->Updated = time();
        return $this;
    }
    
    public function isComplete(): ?bool
    {
        return $this->Complete;
    }
    
    public function setComplete(bool $Complete)
    {
        $this->Complete = $Complete;
        $this->Updated = time();
        return $this;
    }
    
    public function getNotes(): ?string
    {
        return $this->Notes;
    }
    
    public function setNotes(string $Notes)
    {
        $this->Notes = $Notes;
        $this->Updated = time();
        return $this;
    }
}
