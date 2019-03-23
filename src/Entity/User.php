<?php

namespace App\Entity;

use App\Service\User\SignInDiscord;
use App\Utils\Random;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="users",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="sso", columns={"sso"}),
 *          @ORM\Index(name="session", columns={"session"}),
 *          @ORM\Index(name="username", columns={"username"}),
 *          @ORM\Index(name="email", columns={"email"}),
 *          @ORM\Index(name="is_banned", columns={"is_banned"}),
 *          @ORM\Index(name="api_public_key", columns={"api_public_key"}),
 *          @ORM\Index(name="api_endpoint_access_suspended", columns={"api_endpoint_access_suspended"}),
 *          @ORM\Index(name="sso_discord_id", columns={"sso_discord_id"}),
 *          @ORM\Index(name="sso_discord_avatar", columns={"sso_discord_avatar"}),
 *          @ORM\Index(name="sso_discord_token_expires", columns={"sso_discord_token_expires"}),
 *          @ORM\Index(name="sso_discord_token_access", columns={"sso_discord_token_access"}),
 *          @ORM\Index(name="sso_discord_token_refresh", columns={"sso_discord_token_refresh"}),
 *          @ORM\Index(name="sso_discord_avatar", columns={"sso_discord_avatar"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    const DEFAULT_RATE_LIMIT = 20;
    
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
     * @var bool
     * @ORM\Column(type="boolean", name="is_banned", options={"default" : 0})
     */
    private $banned = false;
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;
    /**
     * The name of the SSO provider
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $sso;
    /**
     * A random hash saved to cookie to retrieve the token
     * @var string
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     */
    private $session;
    /**
     * Username provided by the SSO provider (updates on token refresh)
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $username;
    /**
     * Email provided by the SSO token, this is considered "unique", if someone changes their
     * email then this would in-affect create a new account.
     * @var string
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
    private $avatar = 'http://xivapi.com/img-misc/chat_messengericon_goldsaucer.png';
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $patron = false;
    /**
     * User has 1 Key
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $apiPublicKey = null;
    /**
     * Google Analytics Key
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $apiAnalyticsKey = null;
    /**
     * @var int
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $apiRateLimit = self::DEFAULT_RATE_LIMIT;
    /**
     * User has been suspended from the API
     * @var bool
     * @ORM\Column(type="boolean", name="api_endpoint_access_suspended", options={"default" : 0})
     */
    private $apiEndpointAccessSuspended = false;
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $apiPermissions;

    // -- discord sso

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordId;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordAvatar;
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ssoDiscordTokenExpires = 0;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordTokenAccess;
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ssoDiscordTokenRefresh;
    
    
    // todo legacy ----------------------

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_locked", options={"default" : 0})
     */
    private $locked = false;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_new", options={"default" : 1})
     */
    private $new = true;
    /**
     * The token provided by the SSO provider
     * @var string
     * @ORM\Column(type="text", length=512, nullable=true)
     */
    private $token;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    private $appsMax = 1;
    /**
     * @ORM\Column(type="boolean", name="has_mappy_access", options={"default" : 0})
     */
    private $mappyAccessEnabled = false;
    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $mappyAccessCode = 0;
    /**
     * @var string
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $ssoId;
    
    // todo legacy ----------------------

    public function __construct()
    {
        $this->id           = Uuid::uuid4();
        $this->added        = time();
        $this->apiPublicKey = Random::randomAccessKey();

        $this->generateSession();
    }

    public function generateSession()
    {
        $this->session = Random::randomSecureString(250);
        return;
    }

    public function getAvatar(): string
    {
        if ($this->sso == SignInDiscord::NAME) {
            $this->avatar = sprintf("https://cdn.discordapp.com/avatars/%s/%s.png?size=256",
                $this->ssoDiscordId,
                $this->ssoDiscordAvatar
            );
        }

        return $this->avatar;
    }

    // -------------------------------------------------------
    // Auto Generated Methods
    // -------------------------------------------------------

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

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned)
    {
        $this->banned = $banned;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes = null)
    {
        $this->notes = $notes;

        return $this;
    }

    public function getSso(): ?string
    {
        return $this->sso;
    }

    public function setSso(string $sso)
    {
        $this->sso = $sso;

        return $this;
    }

    public function getSession(): ?string
    {
        return $this->session;
    }

    public function setSession(?string $session = null)
    {
        $this->session = $session;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    public function isPatron(): bool
    {
        return $this->patron;
    }

    public function setPatron(bool $patron)
    {
        $this->patron = $patron;

        return $this;
    }

    public function getApiPublicKey(): ?string
    {
        return $this->apiPublicKey;
    }

    public function setApiPublicKey(string $apiPublicKey)
    {
        $this->apiPublicKey = $apiPublicKey;

        return $this;
    }

    public function getApiAnalyticsKey(): ?string
    {
        return $this->apiAnalyticsKey;
    }

    public function setApiAnalyticsKey(string $apiAnalyticsKey)
    {
        $this->apiAnalyticsKey = $apiAnalyticsKey;

        return $this;
    }

    public function getApiRateLimit(): int
    {
        return $this->apiRateLimit;
    }

    public function setApiRateLimit(int $apiRateLimit)
    {
        $this->apiRateLimit = $apiRateLimit;

        return $this;
    }

    public function isApiEndpointAccessSuspended(): bool
    {
        return $this->apiEndpointAccessSuspended;
    }

    public function setApiEndpointAccessSuspended(bool $apiEndpointAccessSuspended)
    {
        $this->apiEndpointAccessSuspended = $apiEndpointAccessSuspended;

        return $this;
    }

    public function getApiPermissions(): array
    {
        return $this->apiPermissions ? json_decode($this->apiPermissions, true) : [];
    }

    public function setApiPermissions(array $apiPermissions)
    {
        $this->apiPermissions = json_encode($apiPermissions);

        return $this;
    }

    public function addApiPermission($permission): self
    {
        $permissions = $this->getApiPermissions();
        $permissions[] = $permission;
        $this->setApiPermissions($permissions);
        return $this;
    }

    public function getSsoDiscordId(): ?string
    {
        return $this->ssoDiscordId;
    }

    public function setSsoDiscordId(string $ssoDiscordId)
    {
        $this->ssoDiscordId = $ssoDiscordId;

        return $this;
    }

    public function getSsoDiscordAvatar(): ?string
    {
        return $this->ssoDiscordAvatar;
    }

    public function setSsoDiscordAvatar(string $ssoDiscordAvatar)
    {
        $this->ssoDiscordAvatar = $ssoDiscordAvatar;

        return $this;
    }

    public function getSsoDiscordTokenExpires(): ?int
    {
        return $this->ssoDiscordTokenExpires;
    }

    public function setSsoDiscordTokenExpires(int $ssoDiscordTokenExpires)
    {
        $this->ssoDiscordTokenExpires = $ssoDiscordTokenExpires;

        return $this;
    }

    public function getSsoDiscordTokenAccess(): ?string
    {
        return $this->ssoDiscordTokenAccess;
    }

    public function setSsoDiscordTokenAccess(string $ssoDiscordTokenAccess)
    {
        $this->ssoDiscordTokenAccess = $ssoDiscordTokenAccess;

        return $this;
    }

    public function getSsoDiscordTokenRefresh(): ?string
    {
        return $this->ssoDiscordTokenRefresh;
    }

    public function setSsoDiscordTokenRefresh(string $ssoDiscordTokenRefresh)
    {
        $this->ssoDiscordTokenRefresh = $ssoDiscordTokenRefresh;

        return $this;
    }
    
    // ---
    
    public function isLocked(): bool
    {
        return $this->locked;
    }
    
    public function setLocked(bool $locked)
    {
        $this->locked = $locked;
        
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
    
    public function getToken(): ?string
    {
        return $this->token;
    }
    
    public function setToken(string $token)
    {
        $this->token = $token;
        
        return $this;
    }
    
    public function getAppsMax()
    {
        return $this->appsMax;
    }
    
    public function setAppsMax($appsMax)
    {
        $this->appsMax = $appsMax;
        
        return $this;
    }
    
    public function getMappyAccessEnabled()
    {
        return $this->mappyAccessEnabled;
    }
    
    public function setMappyAccessEnabled($mappyAccessEnabled)
    {
        $this->mappyAccessEnabled = $mappyAccessEnabled;
        
        return $this;
    }
    
    public function getMappyAccessCode()
    {
        return $this->mappyAccessCode;
    }
    
    public function setMappyAccessCode($mappyAccessCode)
    {
        $this->mappyAccessCode = $mappyAccessCode;
        
        return $this;
    }
    
    public function getSsoId(): ?string
    {
        return $this->ssoId;
    }
    
    public function setSsoId(string $ssoId)
    {
        $this->ssoId = $ssoId;
        
        return $this;
    }
}
