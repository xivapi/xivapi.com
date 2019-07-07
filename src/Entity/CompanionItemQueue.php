<?php

namespace App\Entity;

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
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="integer")
     */
    private $item;
    /**
     * @ORM\Column(type="integer")
     */
    private $server;
    /**
     * @ORM\Column(type="integer")
     */
    private $queue;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getItem()
    {
        return $this->item;
    }
    
    public function setItem($item)
    {
        $this->item = $item;
        return $this;
    }
    
    public function getServer()
    {
        return $this->server;
    }
    
    public function setServer($server)
    {
        $this->server = $server;
        return $this;
    }
    
    public function getQueue()
    {
        return $this->queue;
    }
    
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }
}
