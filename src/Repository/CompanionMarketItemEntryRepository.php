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
    public function findItemsToUpdate(int $priority, int $limit)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.priority = {$priority}")
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit);
    
        /**
         * Temp ignore Balmung, it's having issues, we'll get through it
         */
        $sql->andWhere('a.server != 26');
    
        return $sql->getQuery()->getResult();
    }
}
