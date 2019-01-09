<?php

namespace App\Service;

use App\Service\LodestoneQueue\RabbitMQ;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;

class Service
{
    /** @var EntityManagerInterface */
    public $em;
    /** @var Cache */
    public $cache;
    /** @var RabbitMQ */
    public $rabbit;

    public function __construct(EntityManagerInterface $em, Cache $cache, RabbitMQ $rabbitMQ)
    {
        $this->em     = $em;
        $this->cache  = $cache;
        $this->rabbit = $rabbitMQ;
    }

    public function persist($object): void
    {
        $this->em->persist($object);
        $this->em->flush();
    }

    public function remove($object): void
    {
        $this->em->remove($object);
        $this->em->flush();
    }

    public function getRepository($class)
    {
        return $this->em->getRepository($class);
    }
}
