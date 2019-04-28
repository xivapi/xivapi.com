<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_queue",
 *     indexes={
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="queue", columns={"queue"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionItemQueueRepository")
 */
class CompanionItemQueue
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
    private $server;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $queue;
    
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
    
    public function getServer(): int
    {
        return $this->server;
    }
    
    public function setServer(int $server)
    {
        $this->server = $server;
        return $this;
    }
    
    public function getQueue(): int
    {
        return $this->queue;
    }
    
    public function setQueue(int $queue)
    {
        $this->queue = $queue;
        return $this;
    }
}
