<?php

namespace App\Common\Repository;

use App\Common\Entity\UserAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAlert::class);
    }
    
    public function findPatrons(bool $patronQueue, int $offset, int $limit)
    {
        $sql = $this->createQueryBuilder('a');
        $sql->join('a.user', 'u')
            ->orderBy('a.lastChecked', 'asc')
            ->setFirstResult($limit * $offset)
            ->setMaxResults($limit);

        if ($patronQueue) {
            $sql->where('u.patron > 0');
        } else {
            $sql->where('u.patron = 0');
        }
        
        return $sql->getQuery()->getResult();
    }
}
