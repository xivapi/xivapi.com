<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="apps",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="google_analytics_id", columns={"google_analytics_id"}),
 *          @ORM\Index(name="level", columns={"level"}),
 *          @ORM\Index(name="api_key", columns={"api_key"}),
 *          @ORM\Index(name="api_rate_limit", columns={"api_rate_limit"}),
 *          @ORM\Index(name="is_restricted", columns={"is_restricted"}),
 *          @ORM\Index(name="is_default", columns={"is_default"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\AppRepository")
 */
class App
{
    const DEFAULT_API_KEY = 'default';

    const RATE_LIMITS = [
        1 => 2,
        2 => 2,
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
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apps")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $googleAnalyticsId = null;
    /**
     * @var string
     * @ORM\Column(type="string", length=400, nullable=true)
     */
    private $description;
    /**
     * @var int
     * @ORM\Column(type="integer", length=2)
     */
    private $level = 2;
    /**
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $apiKey;
    /**
     * @var integer
     * @ORM\Column(type="integer", length=4)
     */
    private $apiRateLimit = 2;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_restricted", options={"default" : 0})
     */
    private $restricted = false;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_default")
     */
    private $default = false;
    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $access = [];
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->generateApiKey();
        $this->added = time();
    }
    
    public function generateApiKey()
    {
        $this->apiKey = substr(str_ireplace('-', null,
            Uuid::uuid4()->toString() . Uuid::uuid4()->toString()), 0, 24
        );
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

    public function isDefault(): bool
    {
        return $this->default;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null)
    {
        $this->description = $description;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;

        $this->setApiRateLimit(
            self::RATE_LIMITS[$this->level]
        );

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
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

    public function isLimited()
    {
        return (time() - $this->added) < 3600;
    }

    public function isRestricted(): bool
    {
        return $this->restricted;
    }

    public function setRestricted(bool $restricted)
    {
        $this->restricted = $restricted;

        return $this;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId = null)
    {
        $this->googleAnalyticsId = $googleAnalyticsId;

        return $this;
    }

    public function hasGoogleAnalytics(): bool
    {
        return !empty($this->googleAnalyticsId);
    }

    public function getAccess(): array
    {
        return $this->access;
    }

    public function setAccess(array $access)
    {
        $this->access = $access;

        return $this;
    }
}
