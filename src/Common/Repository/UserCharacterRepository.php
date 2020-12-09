<?php

namespace App\Common\Repository;

use App\Common\Entity\UserCharacter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserCharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCharacter::class);
    }

    public function findLastUpdated(int $limit)
    {
        $sql = $this->createQueryBuilder('uc');
        $sql->orderBy('uc.updated', 'ASC')
            ->setMaxResults($limit);

        return $sql->getQuery()->getResult();
    }
}
