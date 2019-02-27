<?php

namespace App\Service\Companion;

use App\Entity\CompanionMarketItem;
use App\Entity\CompanionMarketItemEntry;
use App\Entity\CompanionToken;
use App\Repository\CompanionMarketItemRepository;
use App\Service\Common\Time;
use App\Service\Redis\Redis;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionPriority
{
    const SERVER = 'Gilgamesh';
    const CACHE_MARKET_ITEM_IDS = __DIR__.'/CompanionPriority_MarketItemIds.json';
    
    // This is a cronjob boost per priority
    const CRONJOB_BOOSTS = [
        10  => 2,
        11  => 1,
        12  => 4,
        13  => 2,
        14  => 2,
        15  => 3,
        16  => 2,
        17  => 0,
        18  => 0,
        19  => 2,
        20  => 0,
        22  => 0,
        23  => 0,
        24  => 0,
        25  => 0,
        26  => 0,
        27  => 0,
        28  => 0,
        29  => 0,
        30  => 0,
    ];
    
    const CRONJOB_CMD = '* * * * * /usr/bin/php /home/dalamud/xivapi.com/bin/console Companion_AutoUpdateCommand [priority] [queue] >> /home/dalamud/xivapi.com/Companion_AutoUpdateCommand.txt';
    
    // Priority values against a slot of time
    const PRIORITY_VALUES = [
        // 30 minutes
        1800 => 10,
    
        // 1 hour
        3600 => 11,
    
        // 4 hours
        14400 => 12,
    
        // 6 hours
        21600 => 13,
    
        // 12 hours
        43200 => 14,
    
        // 18 hours
        64800 => 15,
    
        // 24 hours
        86400 => 16,
    
        // 30 hours
        108000 => 17,
    
        // 40 hours
        144000 => 18,
    
        // 60 hours
        216000 => 19,
    
        // 80 hours
        288000 => 20,
    
        // 100 hours
        360000 => 21,
    
        // 5 days
        432000 => 22,
    
        // 7 days
        604800 => 23,
    
        // 10 days
        864000 => 24,
    
        // 15 days
        1296000 => 25,
    
        // 20 days
        1728000 => 26,
    
        // 25 days
        2160000 => 27,
    
        // 30 days
        2592000 => 28,
    
        // 40 days
        3456000 => 29,
    
        // 50 days
        4320000 => 30,
    ];
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;
    /** @var CompanionMarketItemRepository */
    private $repository;
    /** @var Companion */
    private $companion;
    /** @var CompanionTokenManager */
    private $companionTokenManager;

    public function __construct(
        EntityManagerInterface $em,
        Companion $companion,
        CompanionTokenManager $companionTokenManager
    ) {
        $this->em                    = $em;
        $this->companion             = $companion;
        $this->companionTokenManager = $companionTokenManager;
        $this->repository            = $this->em->getRepository(CompanionMarketItem::class);
        $this->console               = new ConsoleOutput();
    }
    
    /**
     * Fetch the history for an item and work out the average
     */
    public function fetchLatestHistory(?bool $skip = false, ?int $itemId = null)
    {
        $this->console->writeln('Fetching item historic values');
        $this->console->writeln('-----------------------------');
        
        // grab item ids
        $ids   = CompanionItems::items();
        $total = count($ids);
        
        $section = $this->console->section();
        $section->writeln("Processing item priority for: {$total} items");
        
        // Set token to a specific one
        $this->companion->setCompanionApiToken(
            $this->companionTokenManager->getCompanionTokenForServer(self::SERVER)->getToken()
        );
        
        foreach ($ids as $i => $id) {
            // if we're just doing 1 item, skip ones we haven't set
            if ($itemId && $itemId != $id) {
                continue;
            }
            
            $item = Redis::Cache()->get("xiv_Item_{$id}");
            $lead = "[{$i} / {$total} :: {$id} :: {$item->Name_en}]";
            
            // grab market item
            $obj = $this->repository->findOneBy(['item' => $id]) ?: new CompanionMarketItem();
            
            // skip if already done (and told to do so
            if ($skip && $obj->getUpdated()) {
                continue;
            }
            
            $obj->setUpdated(time())
                ->setItem($id)
                ->setItemSearchCategory($item->ItemSearchCategory->ID);
            
            // get market history
            $section->overwrite("{$lead} Getting purchase history ...");
            try {
                $response = $this->companion->getItemHistory($id);
            } catch (\Exception $ex) {
                $section->overwrite("{$lead} !!! Exception thrown, skipping ....");
                sleep(5);
                continue;
            }
            
            // set history count
            $obj->setHistoryCount(count($response->history));
            
            // if it has no history, continue
            if (empty($response->history)) {
                $section->overwrite("{$lead} No sale history.");
                $obj->setHasSaleHistory(false);
                $this->em->persist($obj);
                continue;
            }
            
            $avgPurchaseDuration = [];
            $avgPurchasePrice    = [];
            $avgPurchasePriceHq  = [];
            $lastPurchaseDate    = 0;
            
            foreach ($response->history as $row) {
                // reduce purchase time down to seconds
                $row->buyRealDate = round($row->buyRealDate / 1000, 0);
                
                // add avg price
                $row->hq
                    ? $avgPurchasePriceHq[] = $row->sellPrice
                    : $avgPurchasePrice[] = $row->sellPrice;
                
                // if no $last time, just set it and move on
                if ($lastPurchaseDate === 0) {
                    $lastPurchaseDate = $row->buyRealDate;
                    $obj->setLastSaleDate($row->buyRealDate);
                    continue;
                }

                // work out time from next sale
                $difference = $lastPurchaseDate - $row->buyRealDate;
                
                // ignore time differences of 0
                if ($difference == 0) {
                    continue;
                }
                
                $avgPurchaseDuration[] = $difference;
                
                // update last time
                $lastPurchaseDate = $row->buyRealDate;
            }
            
            $obj->setAvgSaleDuration(
                empty($avgPurchaseDuration) ? 0 : round(array_sum($avgPurchaseDuration) / count($avgPurchaseDuration))
            );
    
            $obj->setAvgSalePrice(
                empty($avgPurchasePrice) ? 0 : round(array_sum($avgPurchasePrice) / count($avgPurchasePrice))
            );
    
            $obj->setAvgSalePriceHq(
                empty($avgPurchasePriceHq) ? 0 : round(array_sum($avgPurchasePriceHq) / count($avgPurchasePriceHq))
            );
    
            $this->em->persist($obj);
            
            if ($i % 50 === 0) {
                $section->overwrite("{$lead} Saving!");
                $this->em->flush();
                $this->em->clear();
                sleep(2);
            }
        }
    
        $this->em->flush();
        $this->em->clear();
        
        $section->writeln('Done!');
    }
    
    /**
     * Calculate the priority value for an item
     */
    public function calculatePriorityValues(?int $itemId = null)
    {
        $this->console->writeln('Calculating Companion Item Priority');
        $this->console->writeln('-----------------------------------');
        
        $items = $this->repository->findAll();
        
        /** @var CompanionMarketItem $item */
        $section = $this->console->section();
        foreach ($items as $i => $item) {
            // if we're calculating the priority for a specific item
            if ($itemId && $item->getItem() !== $itemId) {
                continue;
            }
            
            // set priority to a default 99
            $item->setPriority(99);
            
            // loop through priority times.
            if ($item->getAvgSaleDuration() > 1) {
                // priority is based on seconds, default is 99
                foreach (self::PRIORITY_VALUES as $unix => $value) {
                    // calculate avg sale duration
                    if ($item->getAvgSaleDuration() < $unix) {
                        $item->setPriority($value);
                        break;
                    }
                }
            }
            
            $section->overwrite("[{$i}] Item {$item->getItem()} = {$item->getPriority()}");
            $this->em->merge($item);
            
            if ($i % 50 == 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
    
        $this->em->flush();
        $this->em->clear();
    }
    
    /**
     * Calculate the priority cronjob allocation
     */
    public function calculatePriorityCronJobs()
    {
        $repo = $this->em->getRepository(CompanionMarketItemEntry::class);
        
        $tableData = [];
        $totalCronJobs = 0;
        
        $this->console->writeln('Calculating cronjob table...');
        foreach (self::PRIORITY_VALUES as $time => $priority) {
            $items = $repo->findBy([ 'priority' => $priority ]);
            $total = count($items);
            
            $boost = self::CRONJOB_BOOSTS[$priority] ?? 1;

            // takes about 3 seconds to do
            $timeToComplete      = ceil($total * CompanionMarketUpdater::ESTIMATED_QUERY_TIME);
            $timeToCompleteFinal = Carbon::createFromTimestamp(time() + ($timeToComplete / ($boost ?: 1)));
            $cronjobs = ceil($timeToComplete / $time) + $boost;

            $this->em->clear();
            
            $tableData[] = [
                $priority,
                Time::countdown($time),
                number_format($total),
                Time::countdown(ceil($timeToComplete / ($boost ?: 1))),
                $timeToCompleteFinal,
                $cronjobs
            ];
    
            $totalCronJobs += $cronjobs;
        }
    
        // Print table
        $table = new Table($this->console);
        $table
            ->setHeaders([
                'Priority',
                'Time Interval',
                'Total Items',
                'TTC',
                'TTC Date',
                'Cronjobs'
            ])
            ->setRows($tableData)
            ->render();
        
        $this->console->writeln([
            '', 'Crons', ''
        ]);
          
        // print cronjob files and save to txt file
        unlink(__DIR__.'/CronJobs.txt');
        foreach ($tableData as $data) {
            [$priority, $a, $b, $c, $d, $totalCronJobs] = $data;
            
            foreach(range(1, $totalCronJobs) as $cronjobNumber) {
                $arguments = [
                    '[priority]' => $priority,
                    '[queue]'    => $cronjobNumber,
                ];
                
                $cron = str_ireplace(array_keys($arguments), $arguments, self::CRONJOB_CMD);
                
                $this->console->writeln(" {$cron}");
                file_put_contents(__DIR__.'/CronJobs.txt', $cron . PHP_EOL, FILE_APPEND);
            }
    
            $this->console->writeln("");
            file_put_contents(__DIR__.'/CronJobs.txt', PHP_EOL, FILE_APPEND);
        }
    }
}
