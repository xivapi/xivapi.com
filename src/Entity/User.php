<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    const MAX_APPS = [
        1 => 1,
        2 => 1,
        3 => 5,
        4 => 10,
        5 => 20,
    ];

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
     * The name of the SSO provider
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $sso;
    /**
     * @var string
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $ssoId;
    /**
     * @var string
     * A random hash saved to cookie to retrieve the token
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $session;
    /**
     * @var string
     * The token provided by the SSO provider
     * @ORM\Column(type="text", length=512, nullable=true)
     */
    private $token;
    /**
     * @var string
     * Username provided by the SSO provider (updates on token refresh)
     * @ORM\Column(type="string", length=64)
     */
    private $username;
    /**
     * @var string
     * Email provided by the SSO token, this is considered "unique", if someone changes their
     * email then this would in-affect create a new account.
     * @ORM\Column(type="string", length=128)
     */
    private $email;
    /**
     * Either provided by SSO provider or default
     *
     *  DISCORD: https://cdn.discordapp.com/avatars/<USER ID>/<AVATAR ID>.png?size=256
     *
     * @var string
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $avatar;
    /**
     * @var int
     * @ORM\Column(type="integer", length=2)
     */
    private $level = 2;
    /**
     * @ORM\OneToMany(targetEntity="App", mappedBy="user")
     */
    private $apps;
    /**
     * @var int
     * @ORM\Column(type="integer", length=16)
     */
    private $appsMax = 1;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_banned")
     */
    private $banned = false;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->session = Uuid::uuid4()->toString() . Uuid::uuid4()->toString() . Uuid::uuid4()->toString();
        $this->apps = new ArrayCollection();
        $this->added = time();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getSso()
    {
        return $this->sso;
    }

    public function setSso($sso)
    {
        $this->sso = $sso;

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
    
    public function getSsoId(): string
    {
        return $this->ssoId;
    }
    
    public function setSsoId(string $ssoId)
    {
        $this->ssoId = $ssoId;
        
        return $this;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    public function getToken()
    {
        return json_decode($this->token);
    }

    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    public function getAvatar(): string
    {
        $token = $this->getToken();
        
        if (empty($token->avatar) || stripos($this->avatar, 'xivapi.com') !== false) {
            return 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png';
        }
        
        $this->avatar = sprintf("https://cdn.discordapp.com/avatars/%s/%s.png?size=256",
            $token->id,
            $token->avatar
        );

        return $this->avatar;
    }

    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getApps()
    {
        return $this->apps;
    }

    public function setApps($apps)
    {
        $this->apps = $apps;

        return $this;
    }

    public function addApp(App $key)
    {
        $this->apps[] = $key;
        return $key;
    }

    public function getAppsMax(): int
    {
        return $this->appsMax;
    }

    public function setAppsMax(int $appsMax)
    {
        $this->appsMax = $appsMax;

        return $this;
    }
    
    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function checkBannedStatus()
    {
        if ($this->isBanned()) {
            header("Location: https://discord.gg/MFFVHWC");
            die();
        }
    }
    
    public function setBanned(bool $banned)
    {
        $this->banned = $banned;
        
        return $this;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;

        $this->setAppsMax(
            self::MAX_APPS[$this->level]
        );

        return $this;
    }

    public function isLimited()
    {
        return (time() - $this->added) < 3600;
    }

    public function hasMapAccess()
    {
        return $this->level >= 4;
    }
}
