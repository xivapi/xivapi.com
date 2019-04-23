<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemQueue;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemQueueRepository;
use App\Service\Companion\CompanionConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class MarketQueue
{
    /** @var EntityManagerInterface */
    private $em;
    
    /** @var CompanionMarketItemQueueRepository */
    private $repo;
    
    /** @var CompanionMarketItemEntryRepository */
    private $repoEntries;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em           = $em;
        $this->repo         = $em->getRepository(CompanionMarketItemQueue::class);
        $this->repoEntries  = $em->getRepository(CompanionMarketItemEntry::class);
    }
    
    public function queue()
    {
        $console = new ConsoleOutput();
    
        /**
         * Clear out all current items
         */
        $con = $this->em->getConnection();
        $sql = 'TRUNCATE TABLE companion_market_item_queue';
        $sql = $con->prepare($sql);
        $sql->execute();
    
        /**
         * Add the new entries
         */
        foreach(CompanionConfiguration::QUEUE_CONSUMERS as $priority => $consumers) {
            $updateItems = $this->repoEntries->findBy([ 'priority' => $priority ], [ 'updated' => 'desc' ], 250);
            
            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("No items for priority: {$priority}");
                continue;
            }
            
            foreach (array_chunk($updateItems, CompanionConfiguration::MAX_ITEMS_PER_CRONJOB) as $i => $items) {
                $console->writeln("Adding items for {$priority}, consumer: {$i}");

                /** @var CompanionMarketItemEntry $item */
                foreach ($items as $item) {
                    $sql = sprintf(
                        "INSERT INTO companion_market_item_queue (id,item,priority,consumer,server,region,patreon_queue) VALUES ('%s',%s,%s,%s,%s,%s,%s)",
                        $item->getId(),
                        $item->getItem(),
                        $item->getServer(),
                        $item->getPriority(),
                        $item->getRegion(),
                        $i
                    );
    
                    $sql = $con->prepare($sql);
                    $sql->execute();
                }
            }
        }

        $con->close();
        $console->writeln("Done");
    }
}
