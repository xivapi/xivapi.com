<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionError;
use App\Entity\CompanionMarketItemUpdate;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionErrorRepository;
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
    /** @var CompanionErrorRepository */
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
        $this->repositoryExceptions = $em->getRepository(CompanionError::class);

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
        $table = [];
        foreach ($this->reportSmall as $row) {
            $table[] = implode('', $row);
        }
        
        $message = "<@42667995159330816> - Companion Auto-Update Statistics\n```". implode("\n", $table) ."```";
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
    
        // Get the last updated entry
        $recentUpdate = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'desc' ]);
        $lastUpdate   = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'asc' ]);
    
        // Work out the cycle speed
        $completionDateTimeSeconds = time() + $completionTimeViaConsumers;
        $completionDateTimeSecondsReal = time() + ($recentUpdate->getUpdated() - $lastUpdate->getUpdated());
        
        $completionDateTime = Carbon::createFromTimestamp($completionDateTimeSeconds);
        $completionDateTimeReal = Carbon::createFromTimestamp($completionDateTimeSecondsReal);
        $completionDateFormatted = Carbon::now()->diff($completionDateTime)->format('%d days, %h hr, %i min');
        
        // work out the real time difference
        $actualDifferenceFormatted = Carbon::createFromTimestamp($recentUpdate->getUpdated())->diff(Carbon::createFromTimestamp($lastUpdate->getUpdated()))->format('%d days, %h hr, %i min');
    
        // work out the difference from the real cycle time vs the estimated cycle time
        $cycleRealDiffFormatted = $completionDateTimeReal->diff($completionDateTime)->format('%d days, %h hr, %i min');
    
        $secondsPerItem = round(($this->avgSecondsPerItem / $consumers), 2);
        $updatedRecent  = date('Y-m-d H:i:s', $recentUpdate->getUpdated());
        $updatedOldest  = date('Y-m-d H:i:s', $lastUpdate->getUpdated());
        
        $this->report[$priority] = [
            'Name'               => $name,
            'QueuePriority'      => $priority,
            'QueueConsumers'     => $consumers,
            'SecondsPerItem'     => $secondsPerItem,
            'TotalItems'         => number_format($totalItems),
            'TotalApiRequests'   => number_format($totalItems * 4),
            'UpdatedRecently'    => $updatedRecent,
            'UpdatedLatest'      => $updatedOldest,
            'CycleTime'          => $completionDateFormatted,
            'CycleTimeReal'      => $actualDifferenceFormatted,
            'CycleDifference'    => $cycleRealDiffFormatted,
            'CycleDifferenceSec' => ceil($completionDateTimeSecondsReal - $completionDateTimeSeconds)
            
        ];
    
        $this->reportSmall[$priority] = [
            str_pad("[{$priority} | {$consumers}] {$name}", 25, " ", STR_PAD_RIGHT),
            str_pad(number_format($totalItems), 15, " ", STR_PAD_RIGHT),
            str_pad($completionDateFormatted, 30, " ", STR_PAD_RIGHT),
            str_pad($actualDifferenceFormatted, 30, " ", STR_PAD_RIGHT),
            str_pad($cycleRealDiffFormatted, 30, " ", STR_PAD_RIGHT),
            ceil($completionDateTimeSecondsReal - $completionDateTimeSeconds)
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
        
        /** @var CompanionError $ex */
        foreach($this->repositoryExceptions->findBy([], ['added' => 'desc'], 10) as $ex) {
            $type = 'Unknown';
            
            if (stripos($ex->getMessage(), '340000')) {
                $type = 'Sight Server Error';
            }
    
            if (stripos($ex->getMessage(), '319201')) {
                $type = 'Server Emergency Maintenance';
            }
    
            if (stripos($ex->getMessage(), '111001')) {
                $type = 'SE Account Token Expired';
            }
    
            if (stripos($ex->getMessage(), 'cURL error 28')) {
                $type = 'Sight Timed-Out';
            }
            
            $exceptions[] = [
                'Added'     => $ex->getAdded(),
                'Arguments' => $ex->getException(),
                'Type'      => $type,
                'Hash'      => sha1($ex->getMessage()),
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
            'ReportUpdated'     => time(),
            'Report'            => $this->report,
            'ItemQueuePriority' => $this->updateQueueSizes,
            'DatabaseSqlReport' => $this->getStatisticsView(),
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
