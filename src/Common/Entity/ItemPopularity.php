<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="items_popularity")
 * @ORM\Entity(repositoryClass="App\Common\Repository\ItemPopularityRepository")
 */
class ItemPopularity
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
     * @ORM\Column(type="integer", length=11, unique=true)
     */
    private $item;
    /**
     * @var int
     * @ORM\Column(name="`count`", type="integer", length=32)
     */
    private $count;
    /**
     * @var int
     * @ORM\Column(name="`rank`", type="integer", length=32)
     */
    private $rank;
    
    public function __construct()
    {
        $this->id     = Uuid::uuid4();
        $this->added  = time();
        
        $this->count = 0;
        $this->rank = 0;
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
    
    public function getCount(): int
    {
        return $this->count;
    }
    
    public function setCount(int $count)
    {
        $this->count = $count;
        
        return $this;
    }
    
    public function getRank(): int
    {
        return $this->rank;
    }
    
    public function setRank(int $rank)
    {
        $this->rank = $rank;
        
        return $this;
    }
}
