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
        $console->writeln("Market Item Queue, waiting 20 seconds...");

        // run this 20 seconds in.
        sleep(20);

        $s = microtime(true);
    
        /**
         * Clear out all current items
         */
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare('TRUNCATE TABLE companion_market_item_queue');
        $stmt->execute();
        
        $insertedItems = [];
    
        /**
         * Insert new items
         */
        foreach (CompanionConfiguration::QUEUE_CONSUMERS as $priority) {
            $updateItems = $this->repoEntries->findItemsToUpdate($priority, 500);
            
            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("No items for priority: {$priority}");
                continue;
            }
            
            foreach (array_chunk($updateItems, CompanionConfiguration::MAX_ITEMS_PER_CRONJOB) as $i => $items) {
                $console->writeln("Adding items for {$priority}, consumer: {$i}");
                
                // end if we go above the max consumers, there is never more than 10.
                if ($i > 10) {
                    break;
                }
    
                /** @var CompanionMarketItemEntry $item */
                foreach ($items as $item) {
                    $queued = new CompanionMarketItemQueue(
                        $item->getId(),
                        $item->getItem(),
                        $item->getServer(),
                        $item->getPriority(),
                        $item->getRegion(),
                        $i
                    );
                    
                    $this->em->persist($queued);
                    $insertedItems[] = $item->getId();
                }
                
                $this->em->flush();
            }
            
            $this->em->flush();
        }
    
        /**
         * Inset patreon items
         */
        $console->writeln("Adding Patreon Queues");
        foreach (CompanionConfiguration::QUEUE_CONSUMERS_PATREON as $patreonQueue) {
            $updateItems = $this->repoEntries->findBy([ 'patreonQueue' => $patreonQueue ], [ 'updated' => 'asc' ], 500);
    
            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("(Patreon) No items for priority: {$priority}");
                continue;
            }
    
            /** @var CompanionMarketItemEntry $item */
            foreach ($updateItems as $item) {
                // don't add items we already have queued.
                if (in_array($item->getId(), $insertedItems)) {
                    continue;
                }
                
                $queued = new CompanionMarketItemQueue(
                    $item->getId(),
                    $item->getItem(),
                    $item->getServer(),
                    $item->getPriority(),
                    $item->getRegion(),
                    $i
                );
                
                $queued->setPatreonQueue($item->getPatreonQueue());
        
                $this->em->persist($queued);
            }
    
            $console->writeln("Patreon queue: {$patreonQueue} filled.");
            $this->em->flush();
        }
        
        $this->em->clear();
        
        $duration = round(microtime(true) - $s, 2);
        
        $console->writeln("Done: {$duration} seconds.");
    }
}
