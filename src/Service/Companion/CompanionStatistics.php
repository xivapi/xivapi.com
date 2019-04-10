<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionMarketItemUpdate;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Repository\CompanionMarketItemUpdateRepository;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    const FILENAME = __DIR__ . '/CompanionStatistics.json';

    // max time to keep updates
    const UPDATE_TIME_LIMIT = (60 * 180);

    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionMarketItemUpdateRepository */
    private $repository;
    /** @var CompanionMarketItemEntryRepository */
    private $repositoryEntries;
    /** @var CompanionMarketItemExceptionRepository */
    private $repositoryExceptions;
    /** @var ConsoleOutput */
    private $console;
    
    // stats vars
    private $report = [];
    private $reportSmall = [];
    private $avgSecondsPerItem = 0;
    private $updateQueueSizes = [];

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(CompanionMarketItemUpdate::class);
        $this->repositoryEntries = $em->getRepository(CompanionMarketItemEntry::class);
        $this->repositoryExceptions = $em->getRepository(CompanionMarketItemException::class);

        $this->console = new ConsoleOutput();
    }

    public function run()
    {
        // delete out of date updates
        $this->removeOutOfDateUpdates();
        
        // calculate the avg seconds per item
        $this->setAverageTimePerUpdate();
    
        // Get queue sizes
        $this->setUpdateQueueSizes();
    
        // build priority stats
        foreach (array_keys(CompanionConfiguration::QUEUE_INFO) as $priority) {
            $this->buildQueueStatistics($priority);
        }
    
        // save
        $this->saveStatistics();
    
        // table
        $this->console->writeln("<info>Avg Seconds per Item: {$this->avgSecondsPerItem}</info>");
        $table = new Table($this->console);
        $table->setHeaders(array_keys($this->report[1]))->setRows($this->report);
        $table->setStyle('box')->render();
        
        // discord message
        $table = new Table($this->console);
        $table->setHeaders(array_keys($this->reportSmall[1]))->setRows($this->reportSmall);
        ob_start();
        $table->setStyle('box')->render();
        $tableText = ob_get_clean();
    
        $message = "<@42667995159330816> - Companion Auto-Update Statistics \n ```{$tableText}```";
        Discord::mog()->sendMessage(null, $message);
    }
    
    private function buildQueueStatistics($priority)
    {
        $this->console->writeln("Building stats for queue: {$priority}");
        
        // queue name
        $name = CompanionConfiguration::QUEUE_INFO[$priority] ?? 'Unknown Queue';
    
        // get the total items in this queue
        $totalItems = $this->updateQueueSizes[$priority] ?? 0;
    
        // some queues have no items
        if ($totalItems === 0) {
            return;
        }
        
        $this->console->writeln("- Total Items: {$totalItems}");
    
        // get the number of consumers for this queue
        $consumers = CompanionConfiguration::QUEUE_CONSUMERS[$priority] ?? 0;
    
        // The completion time would be the total items multiple by how many seconds
        // it takes per item, divided by the number of consumers.
        $completionTime = ($totalItems * $this->avgSecondsPerItem);
        $completionTimeViaConsumers = $completionTime / $consumers;
    
        // Work out the cycle speed
        $completionDateTime = Carbon::createFromTimestamp(time() + $completionTimeViaConsumers);
        $completionDateTime = Carbon::now()->diff($completionDateTime)->format('%d days, %h hr, %i min');
    
        // Get the last updated entry
        $recentUpdate = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'desc' ]);
        $lastUpdate   = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'asc' ]);
        
        // work out the real time difference
        $actualDifference = Carbon::createFromTimestamp($recentUpdate->getUpdated())->diff(
            Carbon::createFromTimestamp($lastUpdate->getUpdated())
        )->format('%d days, %h hr, %i min');
    
        // work out the difference from the real cycle time vs the estimated cycle time
        $diffFromEstimationToReal = Carbon::createFromTimestamp($lastUpdate->getUpdated())->diff(
            Carbon::createFromTimestamp(time() + $completionTimeViaConsumers)
        )->format('%d days, %h hr, %i min');
    
        $secondsPerItem = round(($this->avgSecondsPerItem / $consumers), 2);
        $updatedRecent  = date('Y-m-d H:i:s', $recentUpdate->getUpdated());
        $updatedOldest  = date('Y-m-d H:i:s', $lastUpdate->getUpdated());
        
        $this->report[$priority] = [
            'name'              => "[{$priority}] {$name}",
            'consumers'         => $consumers,
            'seconds_per_item'  => $secondsPerItem,
            'total_items'       => number_format($totalItems),
            'total_requests'    => number_format($totalItems * 4),
            'updated_recently'  => $updatedRecent,
            'updated_oldest'    => $updatedOldest,
            'cycle_time'        => $completionDateTime,
            'cycle_real'        => $actualDifference,
            'cycle_diff'        => $diffFromEstimationToReal,
        ];
    
        $this->reportSmall[$priority] = [
            'name'          => "[{$priority}][{$consumers}] {$name}",
            'items'         => number_format($totalItems),
            'cycle_time'    => $completionDateTime,
            'cycle_real'    => $actualDifference,
            'cycle_diff'    => $diffFromEstimationToReal,
        ];
    }
    
    /**
     * Deletes out of date update records
     */
    private function removeOutOfDateUpdates()
    {
        $this->console->writeln('Removing out of date updates...');
        
        $timeout = time() - self::UPDATE_TIME_LIMIT;
        
        /** @var CompanionMarketItemUpdate $update */
        foreach($this->repository->findAll() as $update) {
            if ($update->getAdded() < $timeout) {
                $this->em->remove($update);
            }
        }
        
        $this->em->flush();
    }
    
    /**
     * This sets the average seconds per item based on durations stored in the database.
     */
    private function setAverageTimePerUpdate()
    {
        $this->console->writeln('Calculating average time per item update ...');
        
        $durations = [];
        
        /** @var CompanionMarketItemUpdate $update */
        foreach($this->repository->findAll() as $update) {
            $durations[] = $update->getDuration();
        }
        
        $this->avgSecondsPerItem = round(array_sum($durations) / count($durations), 5);
    }
    
    /**
     * Set the queue sizes for us
     */
    private function setUpdateQueueSizes()
    {
        $this->console->writeln('Setting queue sizes');
        
        foreach($this->getCompanionQueuesView() as $row) {
            $this->updateQueueSizes[$row['priority']] = $row['total'];
        }
    }
    
    /**
     * Get statistics view
     */
    private function getStatisticsView()
    {
        $sql = $this->em->getConnection()->prepare('SELECT * FROM `companion stats` LIMIT 1');
        $sql->execute();
        
        return $sql->fetchAll()[0];
    }
    
    /**
     * @return mixed[]
     */
    private function getCompanionQueuesView()
    {
        $sql = $this->em->getConnection()->prepare('SELECT * FROM `companion queues`');
        $sql->execute();
        
        return $sql->fetchAll();
    }
    
    /**
     * Get exceptions thrown
     */
    public function getExceptions()
    {
        $exceptions = [];
        
        /** @var CompanionMarketItemException $ex */
        foreach($this->repositoryExceptions->findAll() as $ex) {
            $exceptions[] = [
                'arguments' => $ex->getException(),
                'message'   => $ex->getMessage(),
            ];
        }
        
        return $exceptions;
    }
    
    /**
     * Save our statistics
     */
    public function saveStatistics()
    {
        $data = [
            'updated'           => time(),
            'report'            => $this->report,
            'avgSecondsPerItem' => $this->avgSecondsPerItem,
            'updateQueueSizes'  => $this->updateQueueSizes,
            'getStatisticsView' => $this->getStatisticsView(),
        ];
        
        Redis::Cache()->set('stats_CompanionUpdateStatistics', $data, (60 * 60 * 24 * 7));
    }
    
    /**
     * Load our statistics
     */
    public function getStatistics()
    {
        return Redis::Cache()->get('stats_CompanionUpdateStatistics');
    }
}
