<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionMarketItemUpdate;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Repository\CompanionMarketItemUpdateRepository;
use App\Service\Redis\Redis;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    const FILENAME = __DIR__ . '/CompanionStatistics.json';

    // delete all update records older than 1 hour
    const UPDATE_TIME_LIMIT = (60 * 60);

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
    /** @var array */
    private $updates = [];
    /** @var array */
    private $queues = [];
    /** @var array */
    private $data = [];

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
        // grab all update records
        $updates = $this->repository->findAll();

        // remove out of date records
        $this->removeOldUpdateRecords($updates);
        
        if (empty($updates)) {
            return null;
        }

        // organise updates
        $this->organizeUpdates($updates);

        // Get queue sizes
        $this->getQueueSizes();

        // build global stats
        $this->buildStatistics('global');

        // build priority stats
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $priority) {
            $this->buildStatistics($priority);
        }

        $this->saveStatistics();

        // table
        $table = new Table($this->console);
        $table->setHeaders(array_keys($this->data[0]))->setRows($this->data);
        $table->render();
    }

    /**
     * Save our statistics
     */
    public function saveStatistics()
    {
        $data = [
            $this->data,
            $this->queues,
            $this->getStatisticsView(),
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
     * Remove old update records
     */
    private function removeOldUpdateRecords(array $updates)
    {
        $timelimit = time() - self::UPDATE_TIME_LIMIT;

        /** @var CompanionMarketItemUpdate $update */
        foreach ($updates as $update) {
            if ($update->getAdded() < $timelimit) {
                $this->em->remove($update);
            }
        }

        $this->em->flush();
    }

    /**
     * organise update records into a nice simple table.
     */
    private function organizeUpdates(array $updates)
    {
        /** @var CompanionMarketItemUpdate $update */
        foreach($updates as $update) {
            $this->updates['global'][] = $update;
            $this->updates[$update->getPriority()][] = $update;
        }
    }

    /**
     * Get the queue sizes
     */
    private function getQueueSizes()
    {
        $total = 0;
        foreach($this->getCompanionQueuesView() as $row) {
            $this->queues[$row['priority']] = $row['total'];
            $total += $row['total'];
        }

        $this->queues['global'] = $total;
    }

    /**
     * Build statistics for a particular priority
     */
    private function buildStatistics($priority)
    {
        /** @var CompanionMarketItemUpdate[] $updates */
        $updates = $this->updates[$priority] ?? [];
        $total   = count($updates);
        
        if ($total === 0) {
            return;
        }

        //
        // 1) Work out the update update speed
        //
        $timeA = $updates[0]->getAdded();
        $timeB = end($updates)->getAdded();

        // get the number of seconds between the 1st and last update times
        $seconds = $timeB - $timeA;

        // divide this by the number of updates
        $itemsPerSecond = round($total / $seconds, 9);
        $itemsPerSecondFraction = round(1 / $itemsPerSecond, 9);

        //
        // 2) Work out how long it will take to update all entries
        //
        $totalItems = $this->queues[$priority];

        // multiply total items by the number of items per second fraction
        $totalSecondsForAllItems = ceil($totalItems * $itemsPerSecondFraction);

        //
        // 3) Work out the cycle speed
        //
        $completionTime = Carbon::createFromTimestamp(time() + $totalSecondsForAllItems);
        $completionTime = Carbon::now()->diff($completionTime)->format('%d days, %h hr, %i min and %s sec');

        $this->data[$priority] = [
            'name'              => CompanionConfiguration::QUEUE_INFO[$priority] ?? 'All',
            'priority'          => $priority,
            'items_per_second'  => $itemsPerSecond,
            'total_items'       => $totalItems,
            'total_requests'    => $totalItems * 4,
            'completion_time'   => $completionTime,
        ];
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
}
