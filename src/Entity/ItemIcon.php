<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="item_icons",
 *     indexes={
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="status", columns={"status"}),
 *          @ORM\Index(name="lodestone_id", columns={"lodestone_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ItemIconRepository")
 */
class ItemIcon
{
    const STATUS_NO_ICON      = 0;
    const STATUS_COMPLETE     = 1;
    const STATUS_NO_MARKET_ID = 2;
    const LODESTONE_EXCEPTION = 3;
    const STATUS_NO_LDS_ID    = 4;
    
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $item;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $status;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $lodestoneId;
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lodestoneIcon;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }
    
    public function isComplete(): bool
    {
        return (!empty($this->lodestoneId) && !empty($this->lodestoneIcon));
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id)
    {
        $this->id = $id;
        
        return $this;
    }
    
    public function getItem(): ?int
    {
        return $this->item;
    }
    
    public function setItem(int $item)
    {
        $this->item = $item;
        
        return $this;
    }
    
    public function getStatus(): ?int
    {
        return $this->status;
    }
    
    public function setStatus(int $status)
    {
        $this->status = $status;
        
        return $this;
    }
    
    public function getLodestoneId(): ?string
    {
        return $this->lodestoneId;
    }
    
    public function setLodestoneId(string $lodestoneId)
    {
        $this->lodestoneId = $lodestoneId;
        
        return $this;
    }
    
    public function getLodestoneIcon(): ?string
    {
        return $this->lodestoneIcon;
    }
    
    public function setLodestoneIcon(string $lodestoneIcon)
    {
        $this->lodestoneIcon = $lodestoneIcon;
        
        return $this;
    }
}
