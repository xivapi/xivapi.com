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
    
    /**
     * Find the next expiring time
     */
    public function findExpiringAccounts()
    {
        $sql = $this->createQueryBuilder('a');
        $sql->orderBy('a.expiring', 'asc')
            ->where('a.online = 0')
            ->setMaxResults(50)
            ->setFirstResult(0);
    
        return $sql->getQuery()->getResult();
    }
}
