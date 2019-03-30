<?php

namespace App\Service\Lodestone;

use Doctrine\ORM\EntityManagerInterface;

class AbstractService
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
