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

        // Get queue sizes
        $this->setUpdateQueueSizes();
    
        // build priority stats
        foreach (array_keys(CompanionConfiguration::QUEUE_INFO) as $priority) {
            $this->buildQueueStatistics($priority);
        }
    
        // save
        $this->saveStatistics();
    
        // table
        $table = new Table($this->console);
        $table->setHeaders(array_keys($this->report[1]))->setRows($this->report);
        $table->setStyle('box')->render();
        
        // discord message
        $message = [
            "<@42667995159330816> - Companion Auto-Update Statistics"
        ];

        foreach ($this->report as $row) {
            $message[] = "Name: {$row['Name']} - Priority: {$row['QueuePriority']}";
            $message[] = "Items: {$row['TotalItems']} ({$row['TotalApiRequests']} requests)";

            $CycleTime = str_pad($row['CycleTime'], 30, ' ', STR_PAD_RIGHT);
            $CycleTimeReal = str_pad($row['CycleTimeReal'], 30, ' ', STR_PAD_RIGHT);
            $CycleDifference = str_pad($row['CycleDifference'], 30, ' ', STR_PAD_RIGHT);
            $CycleDifferenceSec = str_pad($row['CycleDifferenceSec'], 30, ' ', STR_PAD_RIGHT);

            $message[] = sprintf('%s%s%s%s', $CycleTime, $CycleTimeReal, $CycleDifference, $CycleDifferenceSec);
            $message[] = "---";
        }
        
        Discord::mog()->sendMessage(null, "```". implode("\n", $message) ."```");
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
        
        // Get the expected update time, if one doesn't exist we'll set it as 3 days
        $expectedUpdateSeconds = array_flip(CompanionConfiguration::PRIORITY_TIMES)[$priority] ?? (60 * 60 * 72);

        // Get the actual update time, we skip some of the early ones incase there was a one off error.
        $recent = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'desc' ], 0, 30);
        $oldest = $this->repositoryEntries->findOneBy([ 'priority' => $priority, ], [ 'updated' => 'asc' ], 0, 30);
        $realUpdateSeconds = ($recent->getUpdated() - $oldest->getUpdated());

        // work out the diff from real-fake
        $updateSecondsDiff = ceil($realUpdateSeconds - $expectedUpdateSeconds);


        // convert our estimation and our real into Carbons
        $completionDateTimeEstimation  = Carbon::createFromTimestamp(time() + $expectedUpdateSeconds);
        $completionDateTimeReal        = Carbon::createFromTimestamp(time() + $realUpdateSeconds);

        // compare now against our estimation
        $completionDateTimeEstimationFormatted = Carbon::now()->diff($completionDateTimeEstimation)->format('%d days, %h hr, %i min');

        // compare now against our real time
        $completionDateTimeRealFormatted = Carbon::now()->diff($completionDateTimeReal)->format('%d days, %h hr, %i min');

        // Work out the time difference
        $completionDateTimeDifference = Carbon::now()->diff(Carbon::now()->addSeconds($realUpdateSeconds))->format('%d days, %h hr, %i min');

        $this->report[$priority] = [
            'Name'               => $name,
            'QueuePriority'      => $priority,
            'TotalItems'         => number_format($totalItems),
            'TotalApiRequests'   => number_format($totalItems * 4),
            'CycleTime'          => $completionDateTimeEstimationFormatted,
            'CycleTimeReal'      => $completionDateTimeRealFormatted,
            'CycleDifference'    => $completionDateTimeDifference,
            'CycleDifferenceSec' => $updateSecondsDiff,
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
