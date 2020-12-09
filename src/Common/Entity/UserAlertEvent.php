<?php

namespace App\Common\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_alerts_events")
 * @ORM\Entity(repositoryClass="App\Common\Repository\UserAlertEventsRepository")
 */
class UserAlertEvent
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    private $userId;
    /**
     * @var UserAlert
     * @ORM\ManyToOne(targetEntity="UserAlert", inversedBy="events", cascade={"remove"})
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $userAlert;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $added;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $data;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->added = time();
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
    
    public function getUserId(): string
    {
        return $this->userId;
    }
    
    public function setUserId(string $userId)
    {
        $this->userId = $userId;
        
        return $this;
    }
    
    public function getUserAlert(): UserAlert
    {
        return $this->userAlert;
    }
    
    public function setUserAlert(UserAlert $userAlert)
    {
        $this->userAlert = $userAlert;
        
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
    
    public function getData(): array
    {
        return json_decode($this->data);
    }
    
    public function setData(array $data)
    {
        $this->data = json_encode($data);
        
        return $this;
    }
}
