<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * - This has UpperCase variables as its game content
 * @ORM\Table(
 *     name="companion_errors",
 *     indexes={
 *          @ORM\Index(name="added", columns={"added"}),
 *          @ORM\Index(name="code", columns={"code"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CompanionErrorRepository")
 */
class CompanionError
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
     * @ORM\Column(type="string", length=255)
     */
    private $code;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $exception;
    /**
     * @var string
     * @ORM\Column(type="text")
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code)
    {
        $this->code = $code;
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
