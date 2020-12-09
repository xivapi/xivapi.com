<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_characters")
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserCharacterRepository")
 */
class UserCharacter
{
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
    private $lodestoneId;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=true)
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
    private $main = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $confirmed = false;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $updated = 0;
    
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getLodestoneId(): ?int
    {
        return $this->lodestoneId ?: null;
    }

    public function setLodestoneId(int $lodestoneId)
    {
        $this->lodestoneId = $lodestoneId;

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

    public function isMain(): bool
    {
        return $this->main;
    }

    public function setMain(bool $main)
    {
        $this->main = $main;

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

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function setUpdated(int $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * If updated is above 0, we have sync'd once from XIVAPI
     */
    public function hasSynced()
    {
        return $this->updated > 0;
    }
}
