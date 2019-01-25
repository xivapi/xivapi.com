<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="users_apps",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="is_new", columns={"is_new"}),
 *          @ORM\Index(name="is_banned", columns={"is_banned"}),
 *          @ORM\Index(name="is_locked", columns={"is_locked"}),
 *
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="google_analytics_id", columns={"google_analytics_id"}),
 *          @ORM\Index(name="api_key", columns={"api_key"}),
 *          @ORM\Index(name="api_rate_limit", columns={"api_rate_limit"}),
 *          @ORM\Index(name="api_rate_limit_burst", columns={"api_rate_limit_burst"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserAppRepository")
 */
class UserApp
{
    use UserTrait;

    const DEFAULT_API_KEY = 'default';

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
     * @var string
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $apiKey;
    /**
     * @var integer
     * @ORM\Column(type="integer", length=4)
     */
    private $apiRateLimit = 1;
    /**
     * @var integer
     * @ORM\Column(type="integer", length=4)
     */
    private $apiRateLimitBurst = 1;
    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_ratelimit_modified", options={"default" : 0})
     */
    private $apiRateLimitAutoModified = false;
    /**
     * @var integer
     * @ORM\Column(type="integer", length=4, nullable=true)
     */
    private $apiRateLimitAutoModifiedDate = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->added = time();
        $this->generateApiKey();
    }

    // -------------------------------------------------------
    
    public function generateApiKey()
    {
        $this->apiKey = substr(str_ireplace('-', null,
            Uuid::uuid4()->toString() . Uuid::uuid4()->toString()), 0, 24
        );
    }

    public function rateLimits(int $limit, int $burst): self
    {
        return $this->setApiRateLimit($limit)->setApiRateLimitBurst($burst);
    }

    // -------------------------------------------------------

    public function getUser(): User
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

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId)
    {
        $this->googleAnalyticsId = $googleAnalyticsId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;

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

    public function getApiRateLimitBurst(): int
    {
        return $this->apiRateLimitBurst;
    }

    public function setApiRateLimitBurst(int $apiRateLimitBurst)
    {
        $this->apiRateLimitBurst = $apiRateLimitBurst;

        return $this;
    }

    public function isApiRateLimitAutoModified(): bool
    {
        return $this->apiRateLimitAutoModified;
    }

    public function setApiRateLimitAutoModified(bool $apiRateLimitAutoModified)
    {
        $this->apiRateLimitAutoModified = $apiRateLimitAutoModified;

        return $this;
    }

    public function getApiRateLimitAutoModifiedDate(): ?int
    {
        return $this->apiRateLimitAutoModifiedDate;
    }

    public function setApiRateLimitAutoModifiedDate(?int $apiRateLimitAutoModifiedDate)
    {
        $this->apiRateLimitAutoModifiedDate = $apiRateLimitAutoModifiedDate;

        return $this;
    }


}
