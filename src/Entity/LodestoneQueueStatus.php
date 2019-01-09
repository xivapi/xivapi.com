<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="lodestone_queue_status")
 * @ORM\Entity(repositoryClass="App\Repository\LodestoneQueueStatusRepository")
 */
class LodestoneQueueStatus
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="guid")
     */
    private $id;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $active = true;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $message;
    
    public function __construct()
    {
        $this->id = Uuid::uuid4();
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
    
    public function isActive(): bool
    {
        return $this->active;
    }
    
    public function setActive(bool $active)
    {
        $this->active = $active;
        
        return $this;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function setMessage(string $message)
    {
        $this->message = $message;
        
        return $this;
    }
}
