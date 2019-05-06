<?php

namespace App\Entity;

use App\Service\Lodestone\CharacterService;
use Doctrine\ORM\Mapping as ORM;

class Entity
{
    const STATE_NONE        = 0;
    const STATE_ADDING      = 1;
    const STATE_CACHED      = 2;
    const STATE_NOT_FOUND   = 3;
    const STATE_BLACKLISTED = 4;
    const STATE_PRIVATE     = 5;
    const STATE_DENIED      = 6;

    const PRIORITY_NORMAL   = 0;  // everyone gets this
    const PRIORITY_DEAD     = 1;  // Characters considered dead
    const PRIORITY_LOW      = 2;  // characters that hardly change,
    const PRIORITY_PATRON   = 10; // patreon characters
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    public $id;
    /**
     * @ORM\Column(type="integer", length=2)
     */
    public $state = self::STATE_ADDING;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    public $updated = 0;
    /**
     * @ORM\Column(type="integer", length=16, options={"default": 0})
     */
    public $priority = self::PRIORITY_NORMAL;
    /**
     * @ORM\Column(type="integer", length=16, options={"default": 0})
     */
    public $notFoundChecks = 0;
    /**
     * @ORM\Column(type="integer", length=16, options={"default": 0})
     */
    public $achievementsPrivateChecks = 0;
    /**
     * @ORM\Column(type="integer", length=16)
     */
    public $lastRequest = 0;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getInfo()
    {
        return [
            'State'       => $this->getState(),
            'Updated'     => $this->getUpdated(),
            'Priority'    => $this->getPriority(),
            'IsActive'    => $this->isActive(),
        ];
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
    
    public function getState()
    {
        return $this->state;
    }
    
    public function setState($state)
    {
        $this->updated = time();
        $this->state = $state;
        return $this;
    }

    public function setStateAdding()
    {
        $this->setState(Entity::STATE_ADDING);
        return $this;
    }

    public function setStateCached()
    {
        $this->setState(Entity::STATE_CACHED);
        return $this;
    }

    public function setStateNotFound()
    {
        $this->setState(Entity::STATE_NOT_FOUND);
        return $this;
    }

    public function setStateBlackListed()
    {
        $this->setState(Entity::STATE_BLACKLISTED);
        return $this;
    }

    public function setStatePrivate()
    {
        $this->setState(Entity::STATE_PRIVATE);
        return $this;
    }

    public function isAdding()
    {
        return $this->getState() == self::STATE_ADDING;
    }

    public function isCached()
    {
        return $this->getState() == self::STATE_CACHED;
    }

    public function isNotFound()
    {
        return $this->getState() == self::STATE_NOT_FOUND;
    }

    public function isBlackListed()
    {
        return $this->getState() == self::STATE_BLACKLISTED;
    }

    public function isPrivate()
    {
        return $this->getState() == self::STATE_PRIVATE;
    }

    public function isActive()
    {
        return $this->lastRequest > (time() - CharacterService::ACTIVE_TIMEOUT);
    }

    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setUpdated()
    {
        $this->updated = time();
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->updated = time();
        $this->priority = $priority;
        return $this;
    }
    
    public function getNotFoundChecks()
    {
        return $this->notFoundChecks;
    }
    
    public function setNotFoundChecks($notFoundChecks)
    {
        $this->updated = time();
        $this->notFoundChecks = $notFoundChecks;
        return $this;
    }
    
    public function incrementNotFoundChecks()
    {
        $this->updated = time();
        $this->setNotFoundChecks($this->notFoundChecks+1);
        return $this;
    }
    
    public function getAchievementsPrivateChecks()
    {
        return $this->achievementsPrivateChecks;
    }
    
    public function setAchievementsPrivateChecks($achievementsPrivateChecks)
    {
        $this->updated = time();
        $this->achievementsPrivateChecks = $achievementsPrivateChecks;
        return $this;
    }
    
    public function incrementAchievementsPrivateChecks()
    {
        $this->updated = time();
        $this->setAchievementsPrivateChecks($this->achievementsPrivateChecks+1);
        return $this;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;
        return $this;
    }


}
