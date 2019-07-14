<?php

namespace App\Service\Companion\Updater;

use App\Common\Entity\Maintenance;
use App\Entity\CompanionItem;
use App\Entity\CompanionItemQueue;
use App\Repository\CompanionItemRepository;
use App\Repository\CompanionItemQueueRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionTokenManager;
use Carbon\Carbon;
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
    /** @var Maintenance */
    private $maintenance;
    
    public function __construct(EntityManagerInterface $em, CompanionTokenManager $ctm)
    {
        $this->em           = $em;
        $this->ctm          = $ctm;
        $this->repo         = $em->getRepository(CompanionItemQueue::class);
        $this->repoEntries  = $em->getRepository(CompanionItem::class);

        $this->maintenance = $this->em->getRepository(Maintenance::class)->findOneBy(['id' => 1 ]) ?: new Maintenance();
    }
    
    public function queue()
    {
        $console = new ConsoleOutput();
        $console->writeln("Market Item Queue");

        if ($this->maintenance->isCompanionMaintenance() || $this->maintenance->isGameMaintenance()) {
            $console->writeln("Maintenance is active, stopping...");
            return;
        }

        // run this 15 seconds in.
        sleep(15);

        $s = microtime(true);
    
        /**
         * Clear out all current items
         */
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare('TRUNCATE TABLE companion_market_item_queue');
        $stmt->execute();
        
        $insertedItems = [];
        
        // grab update queues
        $queues = array_keys(CompanionConfiguration::QUEUE_INFO);
        
        // remove 0, we dont update queue 0
        unset($queues[0]);
        
        /**
         * Insert new items
         */
        foreach ($queues as $normalQueue) {
            // grab items
            $updateItems = $this->repoEntries->findItemsToUpdate(
                $normalQueue,
                CompanionConfiguration::MAX_ITEMS_PER_CRONJOB * 5,
                $this->ctm->getOnlineServers()
            );

            // skip queue if no items for that priority
            if (empty($updateItems)) {
                $console->writeln("No items for priority: {$normalQueue}");
                continue;
            }
            
            foreach (array_chunk($updateItems, CompanionConfiguration::MAX_ITEMS_PER_CRONJOB) as $i => $items) {
                $console->writeln("Adding items for {$normalQueue}, consumer: {$i}");
   
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
                CompanionConfiguration::MAX_ITEMS_PER_CRONJOB
            );
    
            // skip queue if no items for that queue
            if (empty($updateItems)) {
                $console->writeln("(Patreon) No items for queue: {$patreonQueue}");
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

        /**
         * Inset manual items
         */
        $console->writeln("Adding Patreon Queues");
        foreach (CompanionConfiguration::QUEUE_CONSUMERS_MANUAL as $manualQueue) {
            $updateItems = $this->repoEntries->findBy(
                [ 'manualQueue' => $manualQueue ],
                [ 'updated' => 'asc' ],
                CompanionConfiguration::MAX_ITEMS_PER_CRONJOB
            );

            // skip queue if no items for that queue
            if (empty($updateItems)) {
                $console->writeln("(Manual) No items for queue: {$manualQueue}");
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
                    ->setQueue($item->getManualQueue());

                $this->em->persist($queued);
            }

            $console->writeln("Manual queue: {$manualQueue} filled.");
            $this->em->flush();
        }
        
        $this->em->clear();
        $duration = round(microtime(true) - $s, 2);
        $console->writeln("Done: {$duration} seconds.");
    }
    
    public function untrackNonVisititems()
    {
        $start = Carbon::now();
        $console = new ConsoleOutput();
        $console = $console->section();
        $console->writeln("Handling tracking state for items");
        
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare('SELECT * FROM companion_items');
        $stmt->execute();
    
        $timeout = time() - (60 * 60 * 72);
        
        foreach ($stmt->fetchAll() as $row) {
            $itemId    = $row['item_id'];
            $lastVisit = $row['last_visit'];
            $queue     = (int)$row['normal_queue'];

            // grab its current queue
            $stmt  = $conn->prepare("SELECT normal_queue, state FROM companion_market_items WHERE item = {$itemId} AND server = 7");
            $stmt->execute();
            $existing = $stmt->fetch();

            // don't do anything regarding non-updated items.
            if ($existing['state'] === 0) {
                continue;
            }

            // only save the existing if it isn't 0, otherwise keep what we have
            $queue = $existing['normal_queue'] > 0 ? $existing['normal_queue'] : $queue;

            // ensure the companion items has its normal_queue updated
            $stmt = $conn->prepare("UPDATE companion_items SET normal_queue = {$queue} WHERE item_id = {$itemId}");
            $stmt->execute();

            $newQueue = $lastVisit < $timeout ? 0 : $queue;
            $console->overwrite("Item: {$itemId} - Queue: {$queue}");

            // update queue depending on activity
            $stmt = $conn->prepare("UPDATE companion_market_items SET normal_queue = {$newQueue} WHERE item = {$itemId}");
            $stmt->execute();
        }
    
        // finished
        $duration = $start->diff(Carbon::now())->format('%h hr, %i min and %s sec');
        $console->writeln("Duration: <comment>{$duration}</comment>");
    }
}
