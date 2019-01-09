<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="lodestone_statistic",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="queue", columns={"queue"}),
 *          @ORM\Index(name="method", columns={"method"}),
 *          @ORM\Index(name="duration", columns={"duration"}),
 *          @ORM\Index(name="request_id", columns={"request_id"}),
 *          @ORM\Index(name="count", columns={"count"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\LodestoneStatisticRepository")
 */
class LodestoneStatistic
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
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $queue;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $method;
    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $duration;
    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $requestId;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $count;
    
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
    
    public function getAdded(): int
    {
        return $this->added;
    }
    
    public function setAdded(int $added)
    {
        $this->added = $added;
        
        return $this;
    }
    
    public function getQueue(): string
    {
        return $this->queue;
    }
    
    public function setQueue(string $queue)
    {
        $this->queue = $queue;
        
        return $this;
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function setMethod(string $method)
    {
        $this->method = $method;
        
        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration)
    {
        $this->duration = $duration;

        return $this;
    }
    
    public function getRequestId(): string
    {
        return $this->requestId;
    }
    
    public function setRequestId(string $requestId)
    {
        $this->requestId = $requestId;
        
        return $this;
    }
    
    public function getCount(): int
    {
        return $this->count;
    }
    
    public function setCount(int $count)
    {
        $this->count = $count;
        
        return $this;
    }
}
