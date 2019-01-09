<?php

namespace App\Command\Lodestone;

use App\Entity\LodestoneQueueStatus;
use App\Entity\LodestoneStatistic;
use App\Repository\LodestoneStatisticRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAutoStatistics extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('GenerateAutoStatistics');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LodestoneStatisticRepository $repo */
        $repo = $this->em->getRepository(LodestoneStatistic::class);

        // delete old rows
        $repo->removeExpiredRows();

        $time1minute = time() - 60;
        $time1hour   = time() - 3600;

        $requests = $repo->getRequestTimeStats();
        $requestOverdue = 0;
        $requestLast = 0;

        foreach($requests as $req) {
            $requestOverdue += ($req['duration'] - 60);
            $requestLast = $req['finish_time'] > $requestLast ? $req['finish_time'] : $requestLast;
        }
        
        // set status based on overdue amount
        $timeSinceLastRequest = time() - $requestLast;
        $timeBacklog = $requestOverdue - $timeSinceLastRequest;
        
        /** @var LodestoneQueueStatus $queueState */
        $queueState = $this->em->getRepository(LodestoneQueueStatus::class)->findAll()[0];
        $queueState->setActive($timeBacklog < 60)->setMessage("Current backlog time: {$timeBacklog}");
        $this->em->persist($queueState);
        $this->em->flush();

        // build stats on remaining rows
        /** @var LodestoneStatistic $ls */
        $stats = (Object)[
            'requests'         => $requests,
            'request_overdue'  => $requestOverdue,
            'request_last'     => $timeSinceLastRequest,
            'request_backlog'  => $timeBacklog,
            
            'average_duration'      => null,
            'average_duration_data' => [],
            'method_stats'          => [],
            'queue_stats'           => [],
            'counts' => (Object)[
                'min' => 0,
                'hr'  => 0,
                'day' => 0,
            ]
        ];

        foreach ($repo->findAll() as $ls) {
            $stats->counts->day++;

            if ($ls->getAdded() >= $time1minute) {
                $stats->counts->min++;
            }

            if ($ls->getAdded() >= $time1hour) {
                $stats->counts->hr++;
            }

            // Avg Duration
            $stats->average_duration_data[] = $ls->getDuration();

            // Count methods
            if (!isset($stats->method_stats[$ls->getMethod()])) {
                $stats->method_stats[$ls->getMethod()] = 0;
            }

            $stats->method_stats[$ls->getMethod()] += 1;

            // Count queues
            if (!isset($stats->queue_stats[$ls->getQueue()])) {
                $stats->queue_stats[$ls->getQueue()] = 0;
            }

            $stats->queue_stats[$ls->getQueue()] += 1;
        }

        $stats->average_duration = count($stats->average_duration_data) > 0
            ? array_sum($stats->average_duration_data) / count($stats->average_duration_data) : 0;

        $stats->average_duration_data = null;

        // save
        file_put_contents(__DIR__.'/../../Service/LodestoneQueue/stats.json', json_encode($stats));
    }
}
