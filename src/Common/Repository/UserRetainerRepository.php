<?php

namespace App\Common\Repository;

use App\Common\Entity\UserRetainer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserRetainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRetainer::class);
    }
    
    /**
     * Find the retainer in the dalamud companion table
     */
    public function findRetainerInCompanionTable($name, $server): ?array
    {
        $sql = 'SELECT * FROM dalamud.companion_retainers WHERE name = :name AND server = :server';
        
        $sql = $this->_em->getConnection()->prepare($sql);
        $sql->bindParam('name', $name);
        $sql->bindParam('server', $server);
        $sql->execute();
        
        $results = $sql->fetch();
        
        return $results ?: null;
    }
}
