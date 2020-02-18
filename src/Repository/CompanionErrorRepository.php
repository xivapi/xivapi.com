<?php

namespace App\Repository;

use App\Entity\CompanionError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanionError::class);
    }

    /**
     * Find all exceptions in the past 1 hour.
     */
    public function findAllRecent()
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where('a.added > :limit')
            ->setParameter('limit', time() - 3600)
            ->orderBy('a.added', 'desc');

        return $sql->getQuery()->getResult();
    }
}
