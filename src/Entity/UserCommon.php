<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;

/**
 * Common attributes all user entities must use.
 */
class UserCommon
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_new", options={"default" : 0})
     */
    private $new = false;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_banned", options={"default" : 0})
     */
    private $banned = false;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_locked", options={"default" : 0})
     */
    private $locked = false;
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
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

    public function getAdded(): int
    {
        return $this->added;
    }

    public function setAdded(int $added)
    {
        $this->added = $added;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->new;
    }

    public function setNew(bool $new)
    {
        $this->new = $new;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned)
    {
        $this->banned = $banned;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked)
    {
        $this->locked = $locked;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes)
    {
        $this->notes = $notes;

        return $this;
    }
}
