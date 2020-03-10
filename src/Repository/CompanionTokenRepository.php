<?php

namespace App\Repository;

use App\Entity\CompanionToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanionToken::class);
    }
    
    /**
     * Find the next expiring time
     */
    public function findExpiringAccounts()
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where('a.online = :online')->setParameter('online', false)
            ->orderBy('a.expiring', 'asc');
    
        return $sql->getQuery()->getResult();
    }
}
