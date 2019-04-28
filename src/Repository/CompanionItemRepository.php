<?php

namespace App\Repository;

use App\Entity\CompanionItem;
use App\Service\Content\GameServers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionItemRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionItem::class);
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
    public function findItemsToUpdate(int $priority, int $limit, array $servers)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.normalQueue = {$priority}")
            ->andWhere('a.server IN ('. implode(',', $servers) .')')
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit);
    
        return $sql->getQuery()->getResult();
    }
}
