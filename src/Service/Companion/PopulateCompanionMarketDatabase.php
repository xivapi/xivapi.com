<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItem;
use App\Entity\CompanionMarketItemEntry;
use App\Repository\CompanionMarketItemRepository;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * This class will populate the companion market database with ids, this should
 * only be done after a priority has been set.
 */
class PopulateCompanionMarketDatabase
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function populate()
    {
        $ids     = CompanionItems::items();
        $section = (new ConsoleOutput())->section();
        $total   = count($ids);
        
        /** @var CompanionMarketItemRepository $repo */
        $repo = $this->em->getRepository(CompanionMarketItem::class);
    
        // loop through sellable items
        foreach ($ids as $i => $id) {
            $section->overwrite("{$i} / {$total} - {$id}");
            
            /** @var CompanionMarketItem $cmi */
            $cmi = $repo->findOneBy([ 'item' => $id ]);
            
            foreach (GameServers::LIST as $serverId => $serverName) {
                $obj = new CompanionMarketItemEntry();
                $obj->setItem($id)
                    ->setPriority($cmi->getPriority())
                    ->setServer($serverId);
                
                $this->em->persist($obj);
            }
            
            $this->em->flush();
            $this->em->clear();
        }
    }
}
