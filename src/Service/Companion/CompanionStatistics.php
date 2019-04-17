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
    const UPDATE_TIME_LIMIT = (60 * 60);

    /** @var EntityManagerInterface */
    private $em;
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
        $this->repositoryEntries = $em->getRepository(CompanionMarketItemEntry::class);
        $this->repositoryExceptions = $em->getRepository(CompanionError::class);

        $this->console = new ConsoleOutput();
    }

    public function run()
    {
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
        $message = [];

        foreach ($this->report as $row) {
            $message[] = "[{$row['Priority']}][{$row['Name']}][Items: {$row['Items']} - {$row['Requests']}]";

            $CycleTime     = str_pad($row['CycleTime'], 15, ' ', STR_PAD_RIGHT);
            $CycleTimeReal = str_pad($row['CycleTimeReal'], 15, ' ', STR_PAD_RIGHT);
            $CycleDiff     = str_pad($row['CycleDiff'], 15, ' ', STR_PAD_RIGHT);
            $CycleDiffSec  = str_pad($row['CycleDiffSec'], 15, ' ', STR_PAD_RIGHT);

            $message[] = sprintf('-- %s%s%s%s', $CycleTime, $CycleTimeReal, $CycleDiff, $CycleDiffSec);
            $message[] = "";
        }
        
        Discord::mog()->sendMessage(null, "<@42667995159330816> - Companion Auto-Update Statistics\n```". implode("\n", $message) ."```");
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
        /** @var CompanionMarketItemEntry $recent */
        /** @var CompanionMarketItemEntry $oldest */
        $oldest = $this->repositoryEntries->findBy([ 'priority' => $priority, ], [ 'updated' => 'asc' ], 1, 50)[0];
        $realUpdateSeconds = (time() - $oldest->getUpdated());

        // work out the diff from real-fake
        $updateSecondsDiff = $realUpdateSeconds - $expectedUpdateSeconds;

        // convert our estimation and our real into Carbons
        $completionDateTimeEstimation  = Carbon::createFromTimestamp(time() + $expectedUpdateSeconds);
        $completionDateTimeReal        = Carbon::createFromTimestamp(time() + $realUpdateSeconds);

        // compare now against our estimation
        $completionDateTimeEstimationFormatted = Carbon::now()->diff($completionDateTimeEstimation)->format('%d-%h:%i');

        // compare now against our real time
        $completionDateTimeRealFormatted = Carbon::now()->diff($completionDateTimeReal)->format('%d-%h:%i');

        // Work out the time difference
        $completionDateTimeDifference = Carbon::now()->diff(Carbon::now()->addSeconds($realUpdateSeconds))->format('%d-%h:%i');

        $this->report[$priority] = [
            'Name'          => $name,
            'Priority'      => $priority,
            'Items'         => number_format($totalItems),
            'Requests'      => number_format($totalItems * 4),
            'CycleTime'     => $completionDateTimeEstimationFormatted,
            'CycleTimeReal' => $completionDateTimeRealFormatted,
            'CycleDiff'     => $completionDateTimeDifference,
            'CycleDiffSec'  => $updateSecondsDiff,
        ];
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
            'ItemPriority' => $this->updateQueueSizes,
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
