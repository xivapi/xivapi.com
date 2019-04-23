<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_queue",
 *     indexes={
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="region", columns={"region"}),
 *          @ORM\Index(name="patreon_queue", columns={"patreon_queue"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionMarketItemQueueRepository")
 */
class CompanionMarketItemQueue
{
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
    private $priority;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $consumer;
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $patreonQueue;
    
    public function __construct(string $id, int $itemId = null, int $serverId = null, int $priority = null, int $region = null, int $consumer = null)
    {
        $this->id       = $id;
        $this->item     = $itemId;
        $this->server   = $serverId;
        $this->priority = $priority;
        $this->region   = $region;
        $this->consumer = $consumer;
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
    
    public function getItem(): int
    {
        return $this->item;
    }
    
    public function setItem(int $item)
    {
        $this->item = $item;
        
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
    
    public function getConsumer(): int
    {
        return $this->consumer;
    }
    
    public function setConsumer(int $consumer)
    {
        $this->consumer = $consumer;
        
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
    
    public function getPatreonQueue(): int
    {
        return $this->patreonQueue;
    }
    
    public function setPatreonQueue(int $patreonQueue)
    {
        $this->patreonQueue = $patreonQueue;
        
        return $this;
    }
}
