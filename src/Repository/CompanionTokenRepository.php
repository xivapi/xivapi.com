<?php

namespace App\Repository;

use App\Entity\CompanionToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionToken::class);
    }
    
    public function findLastUpdated()
    {
        $sql = $this->createQueryBuilder('a');
        $sql->select('a.server')
            ->orderBy('a.lastOnline', 'asc')
            ->setMaxResults(1)
            ->setFirstResult(0);
    
        return $sql->getQuery()->getSingleResult();
    }
}
