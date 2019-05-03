<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_items",
 *     indexes={
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="normal_queue", columns={"normal_queue"}),
 *          @ORM\Index(name="patreon_queue", columns={"patreon_queue"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="region", columns={"region"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionItemRepository")
 */
class CompanionItem
{
    const STATE_UPDATING        = 1;
    const STATE_BOUGHT_FROM_NPC = 2;
    const STATE_NEVER_SOLD      = 3;
    const STATE_OVER_MAX_TIME   = 9;
    
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
    private $updated;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $priority;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $item;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $server;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $region;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $normalQueue;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $patreonQueue;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $state;
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getUpdated(): int
    {
        return $this->updated;
    }
    
    public function setUpdated(int $updated)
    {
        $this->updated = $updated;
        return $this;
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
        
        return $this;
    }
    
    public function getItem(): int
    {
        return $this->item;
    }
    
    public function setItem(int $item)
    {
        $this->item = $item;
        return $this;
    }
    
    public function getServer(): int
    {
        return $this->server;
    }
    
    public function setServer(int $server)
    {
        $this->server = $server;
        return $this;
    }
    
    public function getRegion(): int
    {
        return $this->region;
    }
    
    public function setRegion(int $region)
    {
        $this->region = $region;
        return $this;
    }
    
    public function getNormalQueue(): int
    {
        return $this->normalQueue ?: 0;
    }
    
    public function setNormalQueue(int $normalQueue)
    {
        $this->normalQueue = $normalQueue;
        return $this;
    }
    
    public function getPatreonQueue(): int
    {
        return $this->patreonQueue ?: 0;
    }
    
    public function setPatreonQueue(int $patreonQueue)
    {
        $this->patreonQueue = $patreonQueue;
        return $this;
    }
    
    public function getState(): int
    {
        return $this->state;
    }
    
    public function setState(int $state)
    {
        $this->state = $state;
        return $this;
    }
}
