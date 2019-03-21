<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemException;
use App\Entity\CompanionMarketItemUpdate;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Repository\CompanionMarketItemUpdateRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    const QUEUE_INFO = [
        // name, consumers
        1 => '< 1 hour',
        2 => '< 3 hours',
        3 => '< 12 hours',
        4 => '< 24 hours',
        5 => '< 40 hours',
        6 => '< 72 hours',
    ];

    const STATS_ARRAY = [
        'queue_name'     => null,
        'total_items'    => 0,
        'total_requests' => 0,
        'total_updated'  => 0,
        'last_updated'   => 0,
        'items_per_sec'  => 0,
        'items_per_min'  => 0,
        'items_per_hr'   => 0,
        'req_per_sec'    => 0,
        'req_per_min'    => 0,
        'req_per_hr'     => 0,
        'cycle_speed'    => null,
    ];

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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em                   = $em;
        $this->repository           = $em->getRepository(CompanionMarketItemUpdate::class);
        $this->repositoryEntries    = $em->getRepository(CompanionMarketItemEntry::class);
        $this->repositoryExceptions = $em->getRepository(CompanionMarketItemException::class);

        $this->console = new ConsoleOutput();
    }

    public function run()
    {
        $updates = $this->repository->findStatisticsForPastDay();

        $data = [];
        $data[] = $this->generateStatistics($updates, 'Global', null);

        foreach([1,2,3,4,5,6,7,8,9,10] as $priority) {
            $data[] = $this->processPriorityStatistics($updates, $priority);
        }

        // store
        file_put_contents(
            __DIR__ .'/CompanionStatistics.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );

        // table
        $table = new Table($this->console);
        $table->setHeaders(array_keys(self::STATS_ARRAY))->setRows($data);
        $table->render();
    }

    public function getRecordedStatistics()
    {
        return json_decode(
            file_get_contents(__DIR__ .'/CompanionStatistics.json')
        );
    }

    public function getExceptions()
    {
        return $this->repositoryExceptions->findAll();
    }
    
    public function getStatisticsView()
    {
        $sql = $this->em->getConnection()->prepare('SELECT * FROM `companion stats` LIMIT 1');
        $sql->execute();
        
        return $sql->fetchAll()[0];
    }

    public function getCompanionQueuesView()
    {
        $sql = $this->em->getConnection()->prepare('SELECT * FROM `companion queues`');
        $sql->execute();

        return $sql->fetchAll();
    }

    /**
     * Generate statistics for a given update
     */
    private function generateStatistics($updates, $name, $priority)
    {
        $this->console->writeln("Building stats: {$name}");

        // stats
        $arr = (object)self::STATS_ARRAY;

        [$sec, $min, $hr]     = $this->getRequestSpeeds($updates);

        $arr->queue_name      = $name;
        $arr->items_per_sec   = $sec;
        $arr->items_per_min   = $min;
        $arr->items_per_hr    = $hr;
        $arr->req_per_sec     = $sec * 4;
        $arr->req_per_min     = $min * 4;
        $arr->req_per_hr      = $hr  * 4;
        $arr->total_items     = $this->repositoryEntries->findTotalOfItems($priority);
        $arr->total_requests  = $arr->total_items * 4;
        $arr->total_updated   = count($updates);
        $arr->last_updated    = $this->getLastUpdateTime($updates);
        $arr->cycle_speed     = $this->getCycleSpeed($arr->req_per_sec, $arr->total_requests);

        $arr->items_per_sec   = number_format($arr->items_per_sec);
        $arr->items_per_min   = number_format($arr->items_per_min);
        $arr->items_per_hr    = number_format($arr->items_per_hr);
        $arr->req_per_sec     = number_format($arr->req_per_sec);
        $arr->req_per_min     = number_format($arr->req_per_min);
        $arr->req_per_hr      = number_format($arr->req_per_hr);
        $arr->total_items     = number_format($arr->total_items);
        $arr->total_requests  = number_format($arr->total_requests);
        $arr->total_updated   = number_format($arr->total_updated);

        return (array)$arr;
    }

    /**
     * Build stats on all priority based item entries.
     */
    private function processPriorityStatistics($updates, $priority)
    {
        $filteredUpdates = [];

        /** @var CompanionMarketItemUpdate $itemUpdate */
        foreach ($updates as $itemUpdate) {
            if ($itemUpdate->getPriority() === $priority) {
                $filteredUpdates[] = $itemUpdate;
            }
        }

        return $this->generateStatistics($filteredUpdates, self::QUEUE_INFO[$priority], $priority);
    }

    /**
     * Calculate request speed
     */
    private function getRequestSpeeds($updates)
    {
        $arr = (object)[
            'sec'       => 0,
            'sec_arr'   => [],
            'min'       => 0,
            'min_arr'   => [],
            'hrs'       => 0,
            'hrs_arr'   => [],
        ];

        /** @var CompanionMarketItemUpdate $itemUpdate */
        foreach ($updates as $itemUpdate) {
            $seconds = $itemUpdate->getAdded();
            $minutes = date('z_H_i', $itemUpdate->getAdded());
            $hours   = date('z_H', $itemUpdate->getAdded());

            $arr->sec_arr[$seconds] = isset($arr->sec_arr[$seconds]) ? $arr->sec_arr[$seconds] + 1 : 1;
            $arr->min_arr[$minutes] = isset($arr->min_arr[$minutes]) ? $arr->min_arr[$minutes] + 1 : 1;
            $arr->hrs_arr[$hours]   = isset($arr->hrs_arr[$hours])   ? $arr->hrs_arr[$hours] + 1 : 1;
        }

        $arr->sec = ceil(array_sum($arr->sec_arr) / count(array_filter($arr->sec_arr)));
        $arr->min = ceil(array_sum($arr->min_arr) / count(array_filter($arr->min_arr)));
        $arr->hrs = ceil(array_sum($arr->hrs_arr) / count(array_filter($arr->hrs_arr)));

        return [
            $arr->sec,
            $arr->min,
            $arr->hrs
        ];
    }

    /**
     * Return the last added timestamp
     */
    private function getLastUpdateTime($updates)
    {
        // due to ordering, it will be the first in the last
        /** @var CompanionMarketItemUpdate $last */
        $last = reset($updates);

        return date('Y-m-d H:i:s', $last->getAdded());
    }

    /**
     * Return a countdown of how long it takes to cycle through all items
     */
    private function getCycleSpeed($reqPerSec, $totalRequests)
    {
        if ($reqPerSec == 0 || $totalRequests == 0) {
            $this->console->writeln("reqPerSec = {$reqPerSec} or totalRequests = {$totalRequests} were zero");
            return null;
        }

        // total requests to perform, divided by the number of req per second
        $future   = Carbon::createFromTimestamp(time() + ceil($totalRequests / $reqPerSec));

        return Carbon::now()->diff($future)->format('%d days, %h hr, %i min and %s sec');
    }
}
