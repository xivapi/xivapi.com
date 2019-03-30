<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_entry",
 *     indexes={
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="manual", columns={"manual"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionMarketItemEntryRepository")
 */
class CompanionMarketItemEntry
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
    private $added;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $updated;
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
    private $server;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    private $updates = 0;
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $manual = false;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    private $avgSaleDuration = 0;
    
    public function __construct(int $itemId = null, int $serverId = null, int $priority = null)
    {
        $this->id       = Uuid::uuid4();
        $this->updated  = time();
        $this->added    = time();
        $this->item     = $itemId;
        $this->server   = $serverId;
        $this->priority = $priority;
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
    
    public function getUpdated(): int
    {
        return $this->updated;
    }
    
    public function setUpdated(int $updated)
    {
        $this->updated = $updated;
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
    
    public function getServer(): int
    {
        return $this->server;
    }
    
    public function setServer(int $server)
    {
        $this->server = $server;
        return $this;
    }
    
    public function getUpdates()
    {
        return $this->updates;
    }
    
    public function setUpdates($updates)
    {
        $this->updates = $updates;
        return $this;
    }
    
    public function incUpdates()
    {
        $this->updates++;
        return $this;
    }

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;
        return $this;
    }

    public function getAvgSaleDuration()
    {
        return $this->avgSaleDuration;
    }

    public function setAvgSaleDuration($avgSaleDuration)
    {
        $this->avgSaleDuration = $avgSaleDuration;

        return $this;
    }

    public function getManual()
    {
        return $this->manual;
    }

    public function setManual($manual)
    {
        $this->manual = $manual;

        return $this;
    }
}
