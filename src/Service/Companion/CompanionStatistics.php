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
            'Total Items',
            'Updated 24 Hours',
            'Update Report',
        ];

        foreach (CompanionConfiguration::QUEUE_INFO as $queueNumber => $queueName) {
            $this->console->writeln("Generating statistics for: {$queueNumber}");

            // queues are multiplied by 100
            $queues = range($queueNumber * 100, $queueNumber * 100 + 10);
            $queues = implode(',', $queues);

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
                "SELECT COUNT(*) as total_updates FROM companion_updates WHERE queue IN ({$queues}) AND added > ?"
            );
            $sql->execute([ $queueLength ]);
            $totalUpdates24Hour = $sql->fetch()['total_updates'];

            //
            // Get the total count of items updated within the queues cycle time
            //
            $cycleTime   = array_flip(CompanionConfiguration::PRIORITY_TIMES)[$queueNumber] ?? 0;
            $cycleLength = time() - $cycleTime;

            if ($queueNumber > 0) {
                $sql = $conn->prepare(
                    "SELECT COUNT(*) as total_updates FROM companion_updates WHERE queue IN ({$queues}) AND added > ?"
                );
                $sql->execute([ $cycleLength ]);
                $updatesWithinSchedule = $sql->fetch()['total_updates'];
            } else {
                $updatesWithinSchedule = 0;
            }



            // print update results
            $updateResult = 'No update schedule requirements';
            if ($updatesWithinSchedule > 0) {
                // Work out the percentage of items updated within the cycle time
                $percent      = $updatesWithinSchedule > 0 ? round(($updatesWithinSchedule / $totalItems) * 100) : '-';
                $percentDaily = $totalUpdates24Hour > 0 ? round(($totalUpdates24Hour / $totalItems) * 100) : '-';

                // set report results
                $updateResult = sprintf(
                    "%s / %s (%s%% - Daily: %s%%)",
                    number_format($updatesWithinSchedule),
                    number_format($totalItems),
                    $percent,
                    $percentDaily
                );
            }

            //
            // Add to the table
            //
            $tableData[] = [
                sprintf(
                    "[%s] %s",
                    $queueNumber,
                    $queueName
                ),
                number_format($totalItems),
                number_format($totalUpdates24Hour),
                $updateResult
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

        // discord message
        $message = [
            implode("", [
                str_pad("Name", 35, ' ', STR_PAD_RIGHT),
                str_pad('Total Items', 20, ' ', STR_PAD_RIGHT),
                str_pad('Updated 24 Hours', 20, ' ', STR_PAD_RIGHT),
                'Update Report',
            ])
        ];

        foreach ($tableData as $row) {
            $message[] = sprintf(
                '%s%s%s%s',
                str_pad($row[0], 35, ' ', STR_PAD_RIGHT),
                str_pad($row[1], 20, ' ', STR_PAD_RIGHT),
                str_pad($row[2], 20, ' ', STR_PAD_RIGHT),
                $row[3]
            );
        }
        
        Discord::mog()->sendMessage(538316536688017418, "```". implode("\n", $message) ."```");
    }
}
