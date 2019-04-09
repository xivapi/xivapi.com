<?php

namespace App\Service\Companion\Updater;

use App\Entity\CompanionToken;
use App\Exception\CompanionMarketItemException;
use App\Repository\CompanionMarketItemExceptionRepository;
use App\Service\Companion\CompanionConfiguration;
use App\Service\Companion\CompanionMarket;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Auto-Update item price + history
 */
class MarketUpdater
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $consoleOutput;
    /** @var CompanionMarket */
    private $market;
    /** @var array */
    private $tokens = [];
    /** @var array */
    private $items = [];
    /** @var array */
    private $times = [
        'startTime' => 0,
    ];

    public function __construct(
        EntityManagerInterface $em,
        CompanionMarket $companionMarket
    ) {
        $this->em         = $em;
        $this->market     = $companionMarket;

        $this->console    = new ConsoleOutput();
        $this->times      = (Object)$this->times;
    }

    public function update(int $priority, int $queue, int $patreonQueue = null)
    {
        $this->console("Priority: {$priority} - Queue: {$queue}");
        $this->times->startTime = microtime(true);

        //--------------------------------------------------------------------------------------------------------------

        if ($this->hasExceptionsExceededLimit()) {
            $this->console('Exceptions are above the ERROR_COUNT_THRESHOLD.');
            exit();
        }

        $this->fetchCompanionTokens();

        $this->fetchItemIdsToUpdate($priority, $queue, $patreonQueue);

        print_r($this->items);
    }

    /**
     * Fetches items to auto-update, this is performed here as the entity
     * manager is quite slow for thousands of throughput every second.
     */
    private function fetchItemIdsToUpdate($priority, $queue, $patreonQueue)
    {
        $limit = implode(',', [
            CompanionConfiguration::MAX_ITEMS_PER_CRONJOB * $queue,
            CompanionConfiguration::MAX_ITEMS_PER_CRONJOB
        ]);

        // get items to update
        $this->console('Finding Item IDs to Auto-Update');

        // patreon get their own table.
        $tableName = $patreonQueue ? "companion_market_item_patreon" : "companion_market_item_entry";

        $sql = "
            SELECT  id, item, server, updates FROM {$tableName}
            WHERE priority = {$priority}
            LIMIT {$limit}
        ";

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();

        $this->items = $stmt->fetchAll();
        $this->console('-> Complete');
    }

    /**
     * Fetch the companion tokens.
     */
    private function fetchCompanionTokens()
    {
        /** @var CompanionToken[] $tokens */
        $tokens = $this->em->getRepository(CompanionToken::class)->findAll();

        foreach ($tokens as $token) {
            // skip offline or expired tokens
            if ($token->isOnline() === false) {
                continue;
            }

            $id = GameServers::getServerId($token->getServer());
            $this->tokens[$id] = $token;
        }

        $this->em->close();
    }

    /**
     * Checks exceptions, this is done only at the start
     */
    private function hasExceptionsExceededLimit()
    {
        /** @var CompanionMarketItemExceptionRepository $repository */
        $repository = $this->em->getRepository(CompanionMarketItemException::class);
        return count($repository->findAllRecent()) >= CompanionConfiguration::ERROR_COUNT_THRESHOLD;
    }

    /**
     * Write to log
     */
    private function console($text)
    {
        $this->consoleOutput->writeln(date('Y-m-d H:i:s') . " | {$text}");
    }
}
