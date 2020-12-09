<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="item_views")
 * @ORM\Entity(repositoryClass="App\Common\Repository\ItemViewsRepository")
 */
class ItemViews
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
    private $lastview;
    /**
     * @var int
     * @ORM\Column(type="integer", length=11, unique=true)
     */
    private $item;
    /**
     * @var int
     * @ORM\Column(type="integer", length=32)
     */
    private $previousQueue = 0;
    
    public function __construct()
    {
        $this->id     = Uuid::uuid4();
        $this->added  = time();
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

    public function getLastview(): int
    {
        return $this->lastview;
    }

    public function setLastview(int $lastview)
    {
        $this->lastview = $lastview;

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

    public function getPreviousQueue(): int
    {
        return $this->previousQueue;
    }

    public function setPreviousQueue(int $previousQueue)
    {
        $this->previousQueue = $previousQueue;

        return $this;
    }
}
