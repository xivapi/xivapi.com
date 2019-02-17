<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_market_item_exception",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="exception", columns={"exception"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionMarketItemExceptionRepository")
 */
class CompanionMarketItemException
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
     * @ORM\Column(type="string", nullable=true)
     */
    private $exception;
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;
    
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
    
    public function getException(): string
    {
        return $this->exception;
    }
    
    public function setException(string $exception)
    {
        $this->exception = $exception;
        
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
