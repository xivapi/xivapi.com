<?php

namespace App\Repository;

use App\Entity\CompanionMarketItemEntry;
use App\Service\Companion\CompanionTokenManager;
use App\Service\Content\GameServers;
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
     * Returns a list of items that can be updated, ignoring those with a server offline.
     */
    public function findItemsToUpdate(int $priority, int $limit, int $offset)
    {
        $ignore = [];
        foreach (CompanionTokenManager::SERVERS_OFFLINE as $server) {
            $ignore[] = GameServers::getServerId($server);
        }
        
        $sql = $this->createQueryBuilder('a');
        $sql->where("a.priority = :a")->setParameter('a', $priority)
            ->andWhere("a.server NOT IN (:b)")->setParameter('b', $ignore, Connection::PARAM_INT_ARRAY)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
    
        return $sql->getQuery()->getResult();
    }
}
