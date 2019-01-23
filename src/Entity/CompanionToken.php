<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="companion_tokens",
 *     indexes={
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="online", columns={"online"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionTokenRepository")
 */
class CompanionToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $server;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $online;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function setServer(string $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function setOnline($online): self
    {
        $this->online = $online;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
