<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item",
 *     indexes={
 *          @ORM\Index(name="updated", columns={"updated"}),
 *          @ORM\Index(name="item", columns={"item"}),
 *          @ORM\Index(name="priority", columns={"priority"}),
 *          @ORM\Index(name="avg_sale_price", columns={"avg_sale_price"}),
 *          @ORM\Index(name="last_sale_date", columns={"last_sale_date"}),
 *          @ORM\Index(name="item_search_category", columns={"item_search_category"}),
 *          @ORM\Index(name="has_sale_history", columns={"has_sale_history"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionMarketItemRepository")
 */
class CompanionMarketItem
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $avgSalePrice;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $avgSalePriceHq;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $avgSaleDuration;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lastSaleDate;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $itemSearchCategory;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $historyCount;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $hasSaleHistory = true;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
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
    
    public function getUpdated(): ?int
    {
        return $this->updated;
    }
    
    public function setUpdated(int $updated)
    {
        $this->updated = $updated;
        
        return $this;
    }
    
    public function getItem(): ?int
    {
        return $this->item;
    }
    
    public function setItem(int $item)
    {
        $this->item = $item;
        
        return $this;
    }
    
    public function getPriority(): ?int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
        
        return $this;
    }
    
    public function getAvgSalePrice(): ?int
    {
        return $this->avgSalePrice;
    }
    
    public function setAvgSalePrice(int $avgSalePrice)
    {
        $this->avgSalePrice = $avgSalePrice;
        
        return $this;
    }
    
    public function getAvgSalePriceHq(): ?int
    {
        return $this->avgSalePriceHq;
    }
    
    public function setAvgSalePriceHq(int $avgSalePriceHq)
    {
        $this->avgSalePriceHq = $avgSalePriceHq;
        
        return $this;
    }
    
    public function getAvgSaleDuration(): ?int
    {
        return $this->avgSaleDuration;
    }
    
    public function setAvgSaleDuration(int $avgSaleDuration)
    {
        $this->avgSaleDuration = $avgSaleDuration;
        
        return $this;
    }
    
    public function getLastSaleDate(): ?int
    {
        return $this->lastSaleDate;
    }
    
    public function setLastSaleDate(int $lastSaleDate)
    {
        $this->lastSaleDate = $lastSaleDate;
        
        return $this;
    }
    
    public function getItemSearchCategory(): ?int
    {
        return $this->itemSearchCategory;
    }
    
    public function setItemSearchCategory(int $itemSearchCategory)
    {
        $this->itemSearchCategory = $itemSearchCategory;
        
        return $this;
    }
    
    public function getHistoryCount(): ?int
    {
        return $this->historyCount;
    }
    
    public function setHistoryCount(int $historyCount)
    {
        $this->historyCount = $historyCount;
        
        return $this;
    }
    
    public function hasSaleHistory(): ?bool
    {
        return $this->hasSaleHistory;
    }
    
    public function setHasSaleHistory(bool $hasSaleHistory)
    {
        $this->hasSaleHistory = $hasSaleHistory;
        
        return $this;
    }
}
