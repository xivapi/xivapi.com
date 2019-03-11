<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItemEntry;
use App\Repository\CompanionMarketItemEntryRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionStatistics
{
    /** @var CompanionMarketItemEntryRepository */
    private $repository;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repository = $em->getRepository(CompanionMarketItemEntry::class);
        $this->console = new ConsoleOutput();
    }

    public function run()
    {
        $data = [];

        $queueInfo = [
            // name, consumers
            1 => ['< 1 hour', 1],
            2 => ['< 3 hours', 1],
            3 => ['< 12 hours', 2],
            4 => ['< 24 hours', 2],
            5 => ['< 40 hours', 1],
            6 => ['< 72 hours', 1],
        ];

        foreach([1,2,3,4,5,6] as $queue) {
            $this->console->writeln("Building statistics for queue: {$queue}");

            [$name, $consumers] = $queueInfo[$queue];

            $stats = (Object)[
                'queue'             => $queue,
                'name'              => $name,
                'consumers'         => $consumers,
                'total_items'       => 0,
                'last_updated_item' => 999999999999,
                'req_per_sec'       => 0,
                'req_per_min'       => 0,
                'req_per_hrs'       => 0,
                'update_speed'      => null,
            ];

            $items = $this->repository->findBy([ 'priority' => 1 ]);
            $stats->total_items = count($items);

            $reqPerMin = [];
            $reqPerSec = [];
            $reqPerHrs = [];

            /** @var CompanionMarketItemEntry $item */
            foreach ($items as $item) {
                $sec = date('s', $item->getUpdated());
                $min = date('i', $item->getUpdated());
                $hrs = date('H', $item->getUpdated());

                $reqPerSec[$sec] = isset($reqPerSec[$sec]) ? $reqPerSec[$sec] + 1 : 1;
                $reqPerMin[$min] = isset($reqPerMin[$min]) ? $reqPerMin[$min] + 1 : 1;
                $reqPerHrs[$hrs] = isset($reqPerHrs[$hrs]) ? $reqPerHrs[$hrs] + 1 : 1;

                if ($item->getUpdated() < $stats->last_updated_item) {
                    $stats->last_updated_item = $item->getUpdated();
                }
            }

            $stats->req_per_sec = array_sum($reqPerSec) / count(array_filter($reqPerSec));
            $stats->req_per_min = array_sum($reqPerMin) / count(array_filter($reqPerMin));
            $stats->req_per_hrs = array_sum($reqPerHrs) / count(array_filter($reqPerHrs));

            $stats->last_updated_item = date('Y-m-d H:i:s', $stats->last_updated_item);

            // work out update speed
            $requests = ceil($stats->total_items / $consumers);
            $requests = $requests * $stats->req_per_sec;

            $future = Carbon::createFromTimestamp(time() + $requests);
            $estimation = Carbon::now()->diff($future)->format('%d days, %h hr, %i min and %s sec');
            $stats->update_speed = $estimation;

            $data[$queue] = $stats;
            break;
        }



        $table = new Table($this->console);
        $table
            ->setHeaders(array_keys((array)reset($data)))
            ->setRows($data);

        $table->render();
    }
}
