<?php

namespace App\Entity;

use Companion\Config\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="companion_tokens",
 *     indexes={
 *          @ORM\Index(name="account", columns={"account"}),
 *          @ORM\Index(name="character_id", columns={"character_id"}),
 *          @ORM\Index(name="server", columns={"server"}),
 *          @ORM\Index(name="expiring", columns={"expiring"}),
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
     * @ORM\Column(type="string", length=5)
     */
    private $account;
    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $characterId;
    /**
     * @ORM\Column(type="string", length=32)
     */
    private $server;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    private $expiring = 0;
    /**
     * @ORM\Column(type="boolean")
     */
    private $online = false;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;
    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $token;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getAccount()
    {
        return $this->account;
    }
    
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }
    
    public function getCharacterId()
    {
        return $this->characterId;
    }
    
    public function setCharacterId($characterId)
    {
        $this->characterId = $characterId;
        return $this;
    }
    
    public function getServer()
    {
        return $this->server;
    }
    
    public function setServer($server)
    {
        $this->server = $server;
        return $this;
    }
    
    public function getExpiring()
    {
        return $this->expiring;
    }
    
    public function setExpiring($expiring)
    {
        $this->expiring = $expiring;
        return $this;
    }

    public function hasExpired(): bool
    {
        return time() > $this->expiring;
    }
    
    public function isOnline()
    {
        return $this->online;
    }
    
    public function setOnline($online)
    {
        $this->online = $online;
        return $this;
    }
    
    public function getMessage()
    {
        return $this->message;
    }
    
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    
    public function getToken()
    {
        return $this->token;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
}
