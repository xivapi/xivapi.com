<?php

namespace App\Service\Companion\Updater;

class MarketUpdaterOld
{
    /**
     * todo - deprecated as it might be why im getting banned so easily.
     * @deprecated
     * Update a series of items in a queue.
     * This is an async method which might cause ban.
     */
    public function updateAsync(int $queue)
    {
        // initialize Companion API
        $this->api = new CompanionApi();
        $this->api->useAsync();
        
        /**
         * It feels like SE restart their servers every hour
         */
        $minute = (int)date('i');
        if (in_array($minute, [7,8])) {
            $this->console("Skipping as minute: {$minute}");
            exit();
        }
        
        $this->console("Queue: {$queue}");
        $this->startTime = microtime(true);
        $this->deadline = time() + CompanionConfiguration::CRONJOB_TIMEOUT_SECONDS;
        $this->queue = $queue;
        $this->console('Starting!');
        
        // Build 100 (50 for prices, 50 for history
        foreach (range(0, 100) as $i) {
            $this->requestIds[$i] = Uuid::uuid4()->toString();
        }
        
        //--------------------------------------------------------------------------------------------------------------
        
        // check error status
        $this->checkErrorState();
        
        // fetch companion tokens
        $this->fetchCompanionTokens();
        
        // fetch item ids to update
        $this->fetchItemIdsToUpdate($queue);
        
        if (empty($this->items)) {
            $this->console('No items to update');
            $this->closeDatabaseConnection();
            return;
        }
        
        // check things didn't take too long to start
        $this->checkDeadline();
        
        // 1st pass - Send Requests
        foreach ($this->items as $i => $item) {
            $this->performRequests($i, $item, 'SEND REQUESTS');
        }
        
        $this->checkErrorState();
        
        // check things didn't take too long to start
        $this->checkDeadline();
        
        // sleep
        $this->console("--- Waiting ---");
        sleep(
            mt_rand(
                CompanionConfiguration::DELAY_BETWEEN_REQUEST_RESPONSE[0],
                CompanionConfiguration::DELAY_BETWEEN_REQUEST_RESPONSE[1]
            )
        );
        
        // 2nd pass - Fetch Responses
        foreach ($this->items as $i => $item) {
            [$prices, $history] = $this->performRequests($i, $item, 'FETCH RESPONSES');
            $this->storeMarketData($item, $prices, $history);
        }
        
        // update the database market entries with the latest updated timestamps
        $this->updateDatabaseMarketItemEntries();
        $this->em->flush();
        
        // finish, output completed duration
        $duration = round(microtime(true) - $this->startTime, 1);
        $this->console("-> Completed. Duration: <comment>{$duration}</comment>");
        $this->closeDatabaseConnection();
    }
    
    /**
     * todo - deprecated as part of updateAsync
     * @deprecated
     * Perform market requests
     */
    private function performRequests($i, $item, $stage)
    {
        $i = $i + 1;
        
        $this->checkErrorState();
        
        $itemId     = $item['item'];
        $server     = $item['server'];
        $serverName = GameServers::LIST[$server];
        $serverDc   = GameServers::getDataCenter($serverName);
        
        /** @var CompanionToken $token */
        $token = $this->tokens[$server] ?? null;
        
        if ($token == null) {
            $this->console("No token for: ({$server}) {$serverName} - {$serverDc}, skipping...");
            return [null,null];
        }
        
        // Set server token
        $this->api->Token()->set($token);
        
        // Setup market requests for Price + History
        $requests = [
            $this->requestIds[$i + self::PRICES]  => $this->api->Market()->getItemMarketListings($itemId),
            $this->requestIds[$i + self::HISTORY] => $this->api->Market()->getTransactionHistory($itemId),
        ];
        
        try {
            // Send async requests
            $results = $this->api->Sight()->settle($requests)->wait();
            $results = $this->api->Sight()->handle($results);
            
            // Get the response for the Prices + History
            $prices  = $results->{$this->requestIds[$i + self::PRICES]} ?? null;
            $history = $results->{$this->requestIds[$i + self::HISTORY]} ?? null;
            
            // check if we were rejected
            $this->checkResponseForErrors($item, $prices);
            $this->checkResponseForErrors($item, $prices);
        } catch (\Exception $ex) {
            $this->console("({$i}) - Exception thrown for: {$itemId} on: {$server} {$serverName} - {$serverDc}");
            return [null,null];
        }
        
        // Record to Google Analytics
        GoogleAnalytics::companionTrackItemAsUrl("/{$itemId}/Prices");
        GoogleAnalytics::companionTrackItemAsUrl("/{$itemId}/History");
        
        // log
        $this->console("({$i} {$stage} :: {$itemId} on {$server} {$serverName} - {$serverDc}");
        
        // slow down req/sec
        usleep(
            mt_rand(
                CompanionConfiguration::DELAY_BETWEEN_REQUESTS_MS[0],
                CompanionConfiguration::DELAY_BETWEEN_REQUESTS_MS[1]
            ) * 1000
        );
        
        return [$prices, $history];
    }
}
