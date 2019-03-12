<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionMarketItemUpdate;
use App\Repository\CompanionMarketItemEntryRepository;
use App\Repository\CompanionMarketItemUpdateRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    const QUEUE_INFO = [
        // name, consumers
        1 => ['< 1 hour',   1],
        2 => ['< 3 hours',  2],
        3 => ['< 12 hours', 2],
        4 => ['< 24 hours', 2],
        5 => ['< 40 hours', 1],
        6 => ['< 72 hours', 1],
    ];

    const STATS_ARRAY = [
        'queue'         => null,
        'name'          => null,
        'consumers'     => 0,
        'total_items'   => 0,
        'total_updated' => 0,
        'last_updated'  => 0,
        'req_per_sec'   => 0,
        'req_per_min'   => 0,
        'req_per_hr'    => 0,
        'cycle_speed'   => null,
    ];

    /** @var CompanionMarketItemUpdateRepository */
    private $repository;
    /** @var CompanionMarketItemEntryRepository */
    private $repositoryEntries;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repository        = $em->getRepository(CompanionMarketItemUpdate::class);
        $this->repositoryEntries = $em->getRepository(CompanionMarketItemEntry::class);

        $this->console = new ConsoleOutput();
    }

    public function run()
    {
        $updates = $this->repository->findStatisticsForPastDay();

        $this->processGlobalStatistics($updates);
    }

    /**
     * Build stats on all update entries regardless of priority
     */
    private function processGlobalStatistics($updates)
    {
        $this->console->writeln("Global Statistics");

        // stats
        $arr = (object)self::STATS_ARRAY;

        [$sec, $min, $hr] = $this->getRequestSpeeds($updates);
        $arr->req_per_sec = $sec;
        $arr->req_per_min = $min;
        $arr->req_per_hr  = $hr;

        $arr->total_items = $this->repositoryEntries->findTotalOfItems();
        $arr->total_updated  = count($updates);

        $arr->last_updated = $this->getLastUpdateTime($updates);

        $arr->cycle_speed  = $this->getCycleSpeed($arr->req_per_sec, $arr->total_items);

        $arr = (array)$arr;

        // table
        $table = new Table($this->console);
        $table->setHeaders(array_keys($arr))->setRows([ $arr ]);
        $table->render();
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
            $minutes = (int)floor($itemUpdate->getAdded() / 60);
            $hours   = (int)floor($itemUpdate->getAdded() / 3600);

            $arr->sec_arr[$seconds] = isset($arr->sec_arr[$seconds]) ? $arr->sec_arr[$seconds] + 1 : 1;
            $arr->min_arr[$minutes] = isset($arr->sec_arr[$minutes]) ? $arr->sec_arr[$minutes] + 1 : 1;
            $arr->hrs_arr[$hours]   = isset($arr->sec_arr[$hours])   ? $arr->sec_arr[$hours] + 1 : 1;
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
        if ($reqPerSec == 0|| $totalRequests == 0) {
            $this->console->writeln("reqPerSec = {$reqPerSec} or totalRequests = {$totalRequests} were zero");
            return null;
        }

        // total requests to perform, divided by the number of req per second
        $future   = Carbon::createFromTimestamp(time() + ceil($totalRequests / $reqPerSec));

        return Carbon::now()->diff($future)->format('%d days, %h hr, %i min and %s sec');
    }
}
