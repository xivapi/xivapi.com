<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_retainers")
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserRetainerRepository")
 */
class UserRetainer
{
    const OWNERSHIP_TIMEOUT = 300;
    
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
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $uniq;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $server;
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $confirmed = false;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $confirmItem;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $confirmPrice;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $updated;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * - The ID of the retainer on XIVAPI
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $apiRetainerId;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->updated = time();
        $this->added = time();
    }

    /**
     * Generate a consistent unique id for the retainer
     */
    public static function unique(string $name, string $server)
    {
        $name = strtolower($name);

        return sha1(sprintf('%s_%s', $name, $server));
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
    
    public function getUniq(): ?string
    {
        return $this->uniq;
    }
    
    public function setUniq(string $uniq)
    {
        $this->uniq = $uniq;
        
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function setServer(string $server)
    {
        $this->server = $server;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed)
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function getConfirmItem(): ?int
    {
        return $this->confirmItem;
    }
    
    public function setConfirmItem(int $confirmItem)
    {
        $this->confirmItem = $confirmItem;

        return $this;
    }

    public function getConfirmPrice(): ?int
    {
        return $this->confirmPrice;
    }

    public function setConfirmPrice(int $confirmPrice)
    {
        $this->confirmPrice = $confirmPrice;

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
    
    public function isRecent(): bool
    {
        return $this->updated > time() - self::OWNERSHIP_TIMEOUT;
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
    
    public function nextOwnershipAttempt()
    {
        return $this->updated + self::OWNERSHIP_TIMEOUT;
    }

    public function getApiRetainerId()
    {
        return $this->apiRetainerId;
    }

    public function setApiRetainerId($apiRetainerId)
    {
        $this->apiRetainerId = $apiRetainerId;

        return $this;
    }
    
    public function hasApiRetainerId()
    {
        return !empty($this->apiRetainerId);
    }
}
