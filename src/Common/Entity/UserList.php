<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_lists")
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserListRepository")
 */
class UserList
{
    const NORMAL_TYPE = 0;
    const CUSTOM_FAVOURITES = 10;
    const CUSTOM_RECENTLY_VIEWED = 20;
    const MAX_ITEMS = 20;
    
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="lists")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
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
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $name;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $custom = false;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $customType = self::NORMAL_TYPE;
    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $items = [];
    
    public function __construct()
    {
        $this->id    = Uuid::uuid4();
        $this->added = time();
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
    
    public function getUser(): User
    {
        return $this->user;
    }
    
    public function setUser(User $user)
    {
        $this->user = $user;
        
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
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    public function isCustom(): bool
    {
        return $this->custom;
    }
    
    public function setCustom(bool $custom)
    {
        $this->custom = $custom;
        
        return $this;
    }
    
    public function getCustomType(): ?int
    {
        return $this->customType;
    }
    
    public function setCustomType(int $customType)
    {
        $this->customType = $customType;
        
        return $this;
    }
    
    public function getItems(): array
    {
        return array_values($this->items);
    }
    
    public function setItems(array $items)
    {
        $this->items = $items;
        
        return $this;
    }
    
    public function addItem(int $itemId)
    {
        // ignore non existing ones
        if (in_array($itemId, $this->items)) {
            return $this;
        }
    
        array_unshift($this->items, $itemId);
        array_splice($this->items, self::MAX_ITEMS);
        
        return $this;
    }
    
    public function removeItem(int $itemId)
    {
        $index = array_search($itemId, $this->items);
        unset($this->items[$index]);
        
        return $this;
    }
    
    public function hasItem(int $itemId)
    {
        return array_search($itemId, $this->items) !== false;
    }
}
