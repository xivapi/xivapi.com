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
 *          @ORM\Index(name="region", columns={"region"}),
 *          @ORM\Index(name="patreon_queue", columns={"patreon_queue"}),
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
     * @var int
     * @ORM\Column(type="integer")
     */
    private $region;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $patreonQueue;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $skipped = false;
    
    public function __construct(int $itemId = null, int $serverId = null, int $priority = null, int $region = null)
    {
        $this->id       = Uuid::uuid4();
        $this->updated  = time();
        $this->item     = $itemId;
        $this->server   = $serverId;
        $this->priority = $priority;
        $this->region   = $region;
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

    public function getRegion(): int
    {
        return $this->region;
    }

    public function setRegion(int $region)
    {
        $this->region = $region;

        return $this;
    }

    public function getPatreonQueue(): ?int
    {
        return $this->patreonQueue;
    }

    public function setPatreonQueue(?int $patreonQueue = null)
    {
        $this->patreonQueue = $patreonQueue;

        return $this;
    }

    public function isSkipped()
    {
        return $this->skipped;
    }

    public function setSkipped($skipped)
    {
        $this->skipped = $skipped;
        return $this;
    }
}
