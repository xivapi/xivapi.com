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
        $conn = $this->em->getConnection();

        $tableData = [];
        $tableHeaders = [
            'Name',
            'Queue',
            'Items',
            'Updated 24 Hours',
            'Updated Scheduled',
            'Percent Updated'
        ];

        foreach (CompanionConfiguration::QUEUE_INFO as $queueNumber => $queueName) {
            $this->console->writeln("Generating statistics for: {$queueNumber}");

            //
            // Grab the total number of items
            //
            $sql = $conn->prepare(
                "SELECT COUNT(*) as total_items FROM companion_market_items WHERE normal_queue = ?"
            );
            $sql->execute([ $queueNumber ]);
            $totalItems = $sql->fetch()['total_items'];

            //
            // grab number of updates in past 24 hours
            //
            $queueLength = time() - (60 * 60 * 24);
            $sql = $conn->prepare(
                "SELECT COUNT(*) as total_updates FROM companion_updates WHERE queue = ? AND added > ?"
            );
            $sql->execute([ $queueNumber, $queueLength ]);
            $totalUpdates24Hour = $sql->fetch()['total_updates'];

            //
            // Get the total count of items updated within the queues cycle time
            //
            $cycleTime   = array_flip(CompanionConfiguration::PRIORITY_TIMES)[$queueNumber] ?? 0;
            $cycleLength = time() - $cycleTime;

            if ($queueNumber > 0) {
                $sql = $conn->prepare(
                    "SELECT COUNT(*) as total_updates FROM companion_updates WHERE queue = ? AND added > ?"
                );
                $sql->execute([ $queueNumber, $cycleLength ]);
                $updatesWithinSchedule = $sql->fetch()['total_updates'];
            } else {
                $updatesWithinSchedule = 0;
            }

            // Work out the percentage of items updated within the cycle time
            $percent = $updatesWithinSchedule > 0 ? round(($updatesWithinSchedule / $totalItems) * 100) : '-';

            //
            // Add to the table
            //
            $tableData[] = [
                $queueName,
                $queueNumber,
                $totalItems,
                $totalUpdates24Hour,
                $updatesWithinSchedule > 0 ? $updatesWithinSchedule : '-',
                $percent
            ];
        }


        // table
        $table = new Table($this->console);
        $table->setHeaders($tableHeaders)->setRows($tableData);
        $table->setStyle('box')->render();
    
        // send it late GMT
        if (date('H') != 20) {
            //return;
        }

        /*
        // discord message
        $message = [
            implode("", [
                str_pad("Title", 35, ' ', STR_PAD_RIGHT),
                str_pad('CycleTimeReal', 25, ' ', STR_PAD_RIGHT),
                str_pad('CycleDiff', 25, ' ', STR_PAD_RIGHT),
                'CycleDiffSec',
            ])
        ];

        foreach ($this->report as $row) {
            $CycleTimeReal = str_pad($row['CycleTimeReal'], 25, ' ', STR_PAD_RIGHT);
            $CycleDiff     = str_pad($row['CycleDiff'], 25, ' ', STR_PAD_RIGHT);
            $CycleDiffSec  = $row['CycleDiffSec'];

            $title = sprintf("[%s] %s (%s)", $row['Priority'], $row['Name'], $row['Items']);
            $title = str_pad($title, 35, ' ', STR_PAD_RIGHT);

            $message[] = sprintf('%s%s%s%s',
                $title,
                $CycleTimeReal,
                $CycleDiff,
                $CycleDiffSec
            );
        }
        
        Discord::mog()->sendMessage(538316536688017418, "```". implode("\n", $message) ."```");
        */
    }
}
