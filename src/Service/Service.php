<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class Service
{
    /** @var EntityManagerInterface */
    public $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getRepository($class)
    {
        return $this->em->getRepository($class);
    }
}
