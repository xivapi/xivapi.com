<?php

namespace App\Service\Companion;

use App\Common\ServicesThirdParty\Discord\Discord;
use App\Entity\CompanionItem;
use App\Entity\CompanionError;
use App\Repository\CompanionItemRepository;
use App\Repository\CompanionErrorRepository;
use App\Common\Service\Redis\Redis;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionItemRepository */
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
        $this->repositoryEntries = $em->getRepository(CompanionItem::class);
        $this->repositoryExceptions = $em->getRepository(CompanionError::class);

        $this->console = new ConsoleOutput();
    }

    public function run()
    {
        // Get queue sizes
        $this->setUpdateQueueSizes();
    
        // build priority stats
        foreach (CompanionConfiguration::PRIORITY_TIMES as $queue) {
            $this->buildQueueStatistics($queue);
        }
    
        // save
        $this->saveStatistics();
    
        // table
        $table = new Table($this->console);
        $table->setHeaders(array_keys($this->report[1]))->setRows($this->report);
        $table->setStyle('box')->render();

        // send it late GMT
        if (date('H') != 20) {
            return;
        }
        
        // discord message
        $message = [
            implode("", [
                str_pad("Title", 35, ' ', STR_PAD_RIGHT),
                str_pad('CycleTime', 25, ' ', STR_PAD_RIGHT),
                str_pad('CycleTimeReal', 25, ' ', STR_PAD_RIGHT),
                str_pad('CycleDiff', 25, ' ', STR_PAD_RIGHT),
                str_pad('CycleDiffSec', 25, ' ', STR_PAD_RIGHT),
            ])
        ];

        foreach ($this->report as $row) {
            $CycleTime     = str_pad($row['CycleTime'], 25, ' ', STR_PAD_RIGHT);
            $CycleTimeReal = str_pad($row['CycleTimeReal'], 25, ' ', STR_PAD_RIGHT);
            $CycleDiff     = str_pad($row['CycleDiff'], 25, ' ', STR_PAD_RIGHT);
            $CycleDiffSec  = str_pad($row['CycleDiffSec'], 25, ' ', STR_PAD_RIGHT);

            $title = sprintf("[%s] %s (%s items)", $row['Priority'], $row['Name'], $row['Items']);
            $title = str_pad($title, 35, ' ', STR_PAD_RIGHT);

            $message[] = sprintf('%s%s%s%s%s',
                $title,
                $CycleTime,
                $CycleTimeReal,
                $CycleDiff,
                $CycleDiffSec
            );
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
        
        // Get the expected update time
        $estimatedCycleTime = array_flip(CompanionConfiguration::PRIORITY_TIMES)[$priority] ?? (60 * 60 * 24 * 30);

        // work out how many queues required
        $expectedQueues = $totalItems / CompanionConfiguration::MAX_ITEMS_PER_CRONJOB;
        $expectedQueues = ceil($expectedQueues / ($estimatedCycleTime / 60));

        /** @var CompanionItem $firstItem */
        /** @var CompanionItem $lastItem */
        $firstItem                = $this->repositoryEntries->findOneBy([ 'normalQueue' => $priority, ], [ 'updated' => 'asc' ]);
        $lastItem                 = $this->repositoryEntries->findOneBy([ 'normalQueue' => $priority, ], [ 'updated' => 'desc' ]);
        $formatMultiDay           = '%d days, %H:%I hrs';

        // work out the real cycle time
        $realCycleTime            = abs($lastItem->getUpdated() - $firstItem->getUpdated());
        $estimatedCycleDifference = Carbon::now()->diff(Carbon::now()->addSeconds($estimatedCycleTime))->format($formatMultiDay);
        $realCycleDifference      = Carbon::now()->diff(Carbon::now()->addSeconds($realCycleTime))->format($formatMultiDay);
        $estimationTimeDifference = $realCycleTime - $estimatedCycleTime;
        $difference               = Carbon::now()->diff(Carbon::now()->addSeconds($estimationTimeDifference))->format($formatMultiDay);

        $this->report[$priority] = [
            'Name'          => $name,
            'Priority'      => $priority,
            'ReqQueues'     => $expectedQueues,
            'Items'         => number_format($totalItems),
            'Requests'      => number_format($totalItems * 4),
            'CycleTime'     => $estimatedCycleDifference,
            'CycleTimeSec'  => $estimatedCycleTime,
            'CycleTimeReal' => $realCycleDifference,
            'CycleDiff'     => $difference,
            'CycleDiffSec'  => $estimationTimeDifference,
        ];
    }

    /**
     * Set the queue sizes for us
     */
    private function setUpdateQueueSizes()
    {
        $this->console->writeln('Setting queue sizes');
        
        foreach($this->getCompanionQueuesView() as $row) {
            $this->updateQueueSizes[$row['normal_queue']] = $row['total_items'];
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
            'ItemPriority'      => $this->updateQueueSizes,
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
