<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_source",
 *     indexes={
 *          @ORM\Index(name="item", columns={"item"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionItemQueueRepository")
 */
class CompanionItemSource
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer", unique=true)
     */
    private $item;
    /**
     * @var string
     * @ORM\Column(type="json")
     */
    private $data;
    
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
    
    public function getData(): string
    {
        return $this->data;
    }
    
    public function setData(string $data)
    {
        $this->data = $data;
        return $this;
    }
}
