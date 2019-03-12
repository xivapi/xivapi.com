<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_update",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="server", columns={"server"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionMarketItemUpdateRepository")
 */
class CompanionMarketItemUpdate
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
    
    public function __construct(int $itemId = null, int $serverId = null, int $priority = null)
    {
        $this->id       = Uuid::uuid4();
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

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;

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
}
