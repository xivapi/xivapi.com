<?php

namespace App\Entity;

use App\Service\API\ApiRequest;
use App\Utils\Random;
use Doctrine\ORM\Mapping as ORM;
use XIV\User\User as CommonUser;

class User extends CommonUser
{
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
    private $apiRateLimit = ApiRequest::MAX_RATE_LIMIT_KEY;
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

    public function __construct()
    {
        parent::__construct();
    
        $this->apiPublicKey = Random::randomAccessKey();
    }
    
    public function getApiPermissions(): array
    {
        return $this->apiPermissions ? explode(',', $this->apiPermissions) : [];
    }
    
    public function setApiPermissions(array $apiPermissions)
    {
        $this->apiPermissions = implode(',', $apiPermissions);
        return $this;
    }
    
    public function addApiPermission($permission): self
    {
        $permissions = $this->getApiPermissions();
        $permissions[] = $permission;
        $this->setApiPermissions($permissions);
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
