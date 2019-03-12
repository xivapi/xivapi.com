<?php

namespace App\Repository;

use App\Entity\CompanionMarketItemEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionMarketItemEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionMarketItemEntry::class);
    }
    
    /**
     * Returns a list of items that can be updated with valid servers
     */
    public function findItemsToUpdate(int $priority, int $limit, int $offset, array $servers)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.priority = :a")->setParameter('a', $priority)
            ->andWhere("a.server IN (:b)")->setParameter('b', $servers, Connection::PARAM_INT_ARRAY)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
    
        return $sql->getQuery()->getResult();
    }
}
