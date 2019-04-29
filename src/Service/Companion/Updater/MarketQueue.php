<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionItem;
use App\Entity\CompanionItemQueue;
use App\Repository\CompanionItemRepository;
use App\Repository\CompanionItemQueueRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class MarketQueue
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionTokenManager */
    private $ctm;
    /** @var CompanionItemQueueRepository */
    private $repo;
    /** @var CompanionItemRepository */
    private $repoEntries;
    
    public function __construct(EntityManagerInterface $em, CompanionTokenManager $ctm)
    {
        $this->em           = $em;
        $this->ctm          = $ctm;
        $this->repo         = $em->getRepository(CompanionItemQueue::class);
        $this->repoEntries  = $em->getRepository(CompanionItem::class);
    }
    
    public function queue()
    {
        $console = new ConsoleOutput();
        $console->writeln("Market Item Queue");

        // run this 20 seconds in.
        sleep(10);

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
            // grab items
            $updateItems = $this->repoEntries->findItemsToUpdate($priority, 90, $this->ctm->getOnlineServers());
            
            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("No items for priority: {$priority}");
                continue;
            }
            
            foreach (array_chunk($updateItems, CompanionConfiguration::MAX_ITEMS_PER_CRONJOB) as $i => $items) {
                $console->writeln("Adding items for {$priority}, consumer: {$i}");
   
                /** @var CompanionItem $item */
                foreach ($items as $item) {
                    $queued = new CompanionItemQueue();
                    $queued
                        ->setId($item->getId())
                        ->setItem($item->getItem())
                        ->setServer($item->getServer())
                        ->setQueue($item->getNormalQueue() * 100 + $i);

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
            $updateItems = $this->repoEntries->findBy(
                [ 'patreonQueue' => $patreonQueue ],
                [ 'updated' => 'asc' ],
                count(CompanionConfiguration::QUEUE_CONSUMERS_PATREON) * 15
            );
    
            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("(Patreon) No items for priority: {$priority}");
                continue;
            }
    
            /** @var CompanionItem $item */
            foreach ($updateItems as $item) {
                // don't add items we already have queued.
                if (in_array($item->getId(), $insertedItems)) {
                    continue;
                }
                
                $queued = new CompanionItemQueue();
                $queued
                    ->setId($item->getId())
                    ->setItem($item->getItem())
                    ->setServer($item->getServer())
                    ->setQueue($item->getPatreonQueue());
                
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
