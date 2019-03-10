<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="users",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="sso", columns={"sso"}),
 *          @ORM\Index(name="sso_id", columns={"sso_id"}),
 *          @ORM\Index(name="session", columns={"session"}),
 *          @ORM\Index(name="username", columns={"username"}),
 *          @ORM\Index(name="email", columns={"email"}),
 *          @ORM\Index(name="is_new", columns={"is_new"}),
 *          @ORM\Index(name="is_banned", columns={"is_banned"}),
 *          @ORM\Index(name="is_locked", columns={"is_locked"}),
 *          @ORM\Index(name="api_public_key", columns={"api_public_key"}),
 *          @ORM\Index(name="api_endpoint_access_granted", columns={"api_endpoint_access_granted"}),
 *          @ORM\Index(name="api_endpoint_access_suspended", columns={"api_endpoint_access_suspended"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
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
    private $added;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_new", options={"default" : 1})
     */
    private $new = true;
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
     * A random hash saved to cookie to retrieve the token
     * @var string
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $session;
    /**
     * The token provided by the SSO provider
     * @var string
     * @ORM\Column(type="text", length=512, nullable=true)
     */
    private $token;
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
     * User has 1 Key
     * @var string
     * @ORM\Column(type="string", length=64, unique=true, nullable=true)
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
     * @ORM\Column(type="integer")
     */
    private $apiRateLimit = 0;
    /**
     * API Endpoint Access
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private $apiEndpointAccess;
    /**
     * API Permissions
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $apiEndpointPermissions = [];
    /**
     * API Access has been granted
     * @var bool
     * @ORM\Column(type="boolean", name="api_endpoint_access_granted", options={"default" : 0})
     */
    private $apiEndpointAccessGranted = false;
    /**
     * User has been suspended from the API
     * @var bool
     * @ORM\Column(type="boolean", name="api_endpoint_access_suspended", options={"default" : 0})
     */
    private $apiEndpointAccessSuspended = false;
    
    
    // todo legacy ----------------------
    
    /**
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $avatar;
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
    
    // todo legacy ----------------------

    public function __construct()
    {
        $this->id           = Uuid::uuid4();
        $this->added        = time();
        $this->session      = $this->generateRandomKey();
        $this->apiPublicKey = $this->generateRandomKey();
    }

    private function generateRandomKey()
    {
        return substr(
            str_ireplace('-', null,
                Uuid::uuid4()->toString() .
                Uuid::uuid4()->toString() .
                Uuid::uuid4()->toString()
            ),
            -50
        );
    }

    public function getAvatar(): string
    {
        $token = $this->getToken();
    
        return "https://cdn.discordapp.com/avatars/{$token->id}/{$token->avatar}.png?t=". time();
    }

    public function getToken(): ?\stdClass
    {
        return json_decode($this->token);
    }
    
    public function setToken(string $token)
    {
        $this->token = $token;
        return $this;
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

    public function getSso(): string
    {
        return $this->sso;
    }

    public function setSso(string $sso)
    {
        $this->sso = $sso;

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

    public function getSession(): string
    {
        return $this->session;
    }

    public function setSession(string $session)
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    public function getApiPublicKey(): string
    {
        return $this->apiPublicKey;
    }

    public function setApiPublicKey(string $apiPublicKey)
    {
        $this->apiPublicKey = $apiPublicKey;

        return $this;
    }

    public function getApiAnalyticsKey(): string
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

    public function getApiEndpointAccess(): array
    {
        return $this->apiEndpointAccess;
    }

    public function setApiEndpointAccess(array $apiEndpointAccess)
    {
        $this->apiEndpointAccess = $apiEndpointAccess;

        return $this;
    }

    public function getApiEndpointPermissions()
    {
        return $this->apiEndpointPermissions;
    }

    public function setApiEndpointPermissions($apiEndpointPermissions)
    {
        $this->apiEndpointPermissions = $apiEndpointPermissions;

        return $this;
    }

    public function isApiEndpointAccessGranted(): bool
    {
        return $this->apiEndpointAccessGranted;
    }

    public function setApiEndpointAccessGranted(bool $apiEndpointAccessGranted)
    {
        $this->apiEndpointAccessGranted = $apiEndpointAccessGranted;

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
}
