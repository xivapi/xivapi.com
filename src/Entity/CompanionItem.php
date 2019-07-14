<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_items",
 *     uniqueConstraints = {
 *          @ORM\UniqueConstraint(name="unique_item", columns={"item", "server"})
 *     },
 *     indexes = {
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="normal_queue", columns={"normal_queue"}),
 *          @ORM\Index(name="manual_queue", columns={"normal_queue"}),
 *          @ORM\Index(name="patreon_queue", columns={"patreon_queue"}),
 *          @ORM\Index(name="state", columns={"state"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionItemRepository")
 */
class CompanionItem
{
    const STATE_UPDATING        = 1;
    const STATE_BOUGHT_FROM_NPC = 2;
    const STATE_NEVER_SOLD      = 3;
    const STATE_LOW_LEVEL       = 4;
    const STATE_OVER_MAX_TIME   = 9;
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="integer")
     */
    private $updated;
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
    private $normalQueue;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $manualQueue;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $patreonQueue;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $state;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setUpdated($updated)
    {
        $this->updated = $updated;
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
    
    public function getNormalQueue()
    {
        return $this->normalQueue;
    }
    
    public function setNormalQueue($normalQueue)
    {
        $this->normalQueue = $normalQueue;
        return $this;
    }
    
    public function getManualQueue()
    {
        return $this->manualQueue;
    }
    
    public function setManualQueue($manualQueue)
    {
        $this->manualQueue = $manualQueue;
        return $this;
    }
    
    public function getPatreonQueue()
    {
        return $this->patreonQueue;
    }
    
    public function setPatreonQueue($patreonQueue)
    {
        $this->patreonQueue = $patreonQueue;
        return $this;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }
}
