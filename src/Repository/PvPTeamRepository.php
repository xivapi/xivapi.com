<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\PvPTeam;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PvPTeamRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PvPTeam::class);
    }
    
    public function getUpdateIds(int $priority = 0, int $page = 0)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->select('a.id')
            ->where("a.priority = :a")
            ->setParameter('a', $priority)
            ->andWhere('a.state = '. Entity::STATE_CACHED)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults(ServiceQueues::TOTAL_PVP_TEAM_UPDATES)
            ->setFirstResult(ServiceQueues::TOTAL_PVP_TEAM_UPDATES * $page);
        
        return $sql->getQuery()->getResult();
    }
}
