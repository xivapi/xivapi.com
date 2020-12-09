<?php

namespace App\Common\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="maintenance")
 * @ORM\Entity(repositoryClass="App\Common\Repository\MaintenanceRepository")
 */
class Maintenance
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $mogboard = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $xivapi = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $game = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $lodestone = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $companion = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getMogboard(): int
    {
        return $this->mogboard;
    }

    public function setMogboard(int $mogboard)
    {
        $this->mogboard = $mogboard;
        return $this;
    }

    public function getXivapi(): int
    {
        return $this->xivapi;
    }

    public function setXivapi(int $xivapi)
    {
        $this->xivapi = $xivapi;
        return $this;
    }

    public function getGame(): int
    {
        return $this->game;
    }

    public function setGame(int $game)
    {
        $this->game = $game;
        return $this;
    }

    public function isGameMaintenance()
    {
        return $this->game > 0;
    }

    public function getLodestone(): int
    {
        return $this->lodestone;
    }

    public function setLodestone(int $lodestone)
    {
        $this->lodestone = $lodestone;
        return $this;
    }

    public function isLodestoneMaintenance()
    {
        return $this->lodestone > 0;
    }

    public function getCompanion(): int
    {
        return $this->companion;
    }

    public function setCompanion(int $companion)
    {
        $this->companion = $companion;
        return $this;
    }

    public function isCompanionMaintenance()
    {
        return $this->companion != 0 && time() > $this->companion;
    }
}
