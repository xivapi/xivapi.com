<?php

namespace App\Repository;

use App\Entity\CompanionMarketItemException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionMarketItemExceptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionMarketItemException::class);
    }

    /**
     * Find all exceptions in the past 1 hour.
     */
    public function findAllRecent()
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where('added > :timelimit')
            ->setParameter('timelimit', time() - 3600)
            ->orderBy('a.added', 'desc');

        return $sql->getQuery()->getResult();
    }
}
