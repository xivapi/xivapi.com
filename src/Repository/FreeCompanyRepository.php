<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class FreeCompanyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FreeCompany::class);
    }
    
    public function getUpdateIds(int $priority = 0, int $page = 0)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->select('a.id')
            ->where("a.priority = :a")
            ->setParameter('a', $priority)
            ->andWhere('a.state = '. Entity::STATE_CACHED)
            ->orderBy('a.updated', 'asc')
            ->setMaxResults(ServiceQueues::TOTAL_FREE_COMPANY_UPDATES)
            ->setFirstResult(ServiceQueues::TOTAL_FREE_COMPANY_UPDATES * $page);
        
        return $sql->getQuery()->getResult();
    }
}
