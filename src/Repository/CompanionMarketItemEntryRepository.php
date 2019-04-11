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
     * Find items in DataCenter
     */
    public function findItemsInServers(int $itemId, array $servers)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.item = :a")->setParameter('a', $itemId)
            ->andWhere("a.server IN (:b)")->setParameter('b', $servers, Connection::PARAM_INT_ARRAY);

        return $sql->getQuery()->getResult();
    }
    
    /**
     * Returns a list of items to update
     */
    public function findItemsToUpdate(int $priority, int $limit, int $offset)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.priority = :a")->setParameter('a', $priority)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
    
        return $sql->getQuery()->getResult();
    }

    /**
     * Returns patreo nitems to update
     */
    public function findPatreonItemsToUpdate(int $patreonPriority, int $limit, int $offset)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.patreonQueue = :a")->setParameter('a', $patreonPriority)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $sql->getQuery()->getResult();
    }

    /**
     * Returns a total item count for a given priority.
     */
    public function findTotalOfItems(int $priority = null)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->select('count(a.id)');

        if ($priority) {
            $sql->where("a.priority = :a")->setParameter('a', $priority);
        }

        return $sql->getQuery()->getSingleScalarResult();
    }
}
