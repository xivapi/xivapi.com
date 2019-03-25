<?php

namespace App\Repository;

use App\Entity\CompanionMarketItemUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CompanionMarketItemUpdateRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompanionMarketItemUpdate::class);
    }

    /**
     * Returns a list of items that can be updated with valid servers
     */
    public function findStatisticsForPastDay()
    {
        $oneday = time() - (60 * 60 * 24);

        $sql = $this->createQueryBuilder('a');
        $sql->where("a.added > :a")->setParameter('a', $oneday)->orderBy('a.added', 'desc');

        return $sql->getQuery()->getResult();
    }
}
