<?php

namespace App\Common\Entity;

use App\Common\Constants\UserConstants;
use App\Common\Utils\Random;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(
 *     name="users_sessions",
 *     indexes={
 *          @ORM\Index(name="session", columns={"session"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserSessionRepository")
 */
class UserSession
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    protected $id;
    /**
     * @var string
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $session;
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="sessions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $lastActive;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $site;

    public function __construct(User $user)
    {
        $this->id         = Uuid::uuid4();
        $this->user       = $user;
        $this->lastActive = time();
        $this->site       = getenv('SITE_CONFIG_NAME');
        
        $this->generateSession();
    }
    
    public function generateSession()
    {
        $this->session = Random::randomSecureString(UserConstants::SESSION_LENGTH);
        return;
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
    
    public function getSession(): string
    {
        return $this->session;
    }
    
    public function setSession(string $session)
    {
        $this->session = $session;
        
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
    
    public function getLastActive(): int
    {
        return $this->lastActive;
    }
    
    public function setLastActive(int $lastActive)
    {
        $this->lastActive = $lastActive;
        
        return $this;
    }
}
