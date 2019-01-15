<?php

namespace App\Repository;

use App\Entity\CompanionToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CompanionToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanionToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanionToken[]    findAll()
 * @method CompanionToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanionTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionToken::class);
    }

    // /**
    //  * @return CompanionToken[] Returns an array of CompanionToken objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CompanionToken
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
