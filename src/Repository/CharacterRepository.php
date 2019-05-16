<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Entity;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function getUpdateIds(int $priority = 0, int $page = 0)
    {
        $total = ServiceQueues::TOTAL_CHARACTER_UPDATES;
        $total = $priority == Entity::PRIORITY_PATRON ? ceil($total / 2) : $total;
        
        
        $sql = $this->createQueryBuilder('a');
        $sql->select('a.id')
            ->where("a.priority = :a")
            ->setParameter('a', $priority)
            ->andWhere('a.state = '. Entity::STATE_CACHED)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults(ServiceQueues::TOTAL_CHARACTER_UPDATES)
            ->setFirstResult(ServiceQueues::TOTAL_CHARACTER_UPDATES * $page);
        
        return $sql->getQuery()->getResult();
    }
}
