<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 2;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB    = 20;

    // the total number of items to process per request
    const MAX_ITEMS_PER_REQUEST    = 2;

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS  = 55;

    // the delay between sending requests and asking for request response data
    const CRONJOB_ASYNC_DELAY_MS   = 3200;

    // Avoid updating an item if it was updated X time ago
    const ITEM_UPDATE_DELAY = (60 * 3);

    // how long a time has to be between sales to count against avg
    const ITEM_HISTORY_THRESHOLD = 0;

    // Any items with a lower history value than this, are put in their own queue.
    const ITEM_HISTORY_AVG_REQUIREMENT = 3;

    // Item has less than 5 sales
    const PRIORITY_ITEM_LOW_SALES = 7;

    // Item was added to the database within the past 7 days
    const PRIORITY_ITEM_IS_NEW = 8;

    // default value
    const PRIORITY_TIMES_DEFAULT = 9;

    // Item has not update
    const PRIORITY_ITEM_NEVER_SOLD = 10;
    
    const QUEUE_INFO = [
        // name, consumers
        1 => '< 2 hour',
        2 => '< 6 hours',
        3 => '< 24 hours',
        4 => '< 40 hours',
        5 => '< 60 hours',
        6 => '< 100 hours',
        
        7 => 'Less than 5 sale history',
        8 => 'Item newly added to XIVAPI',
        9 => 'Default Queue',
        10 => 'Never Sold'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 2)   => 1,
        (60 * 60 * 6)   => 2,
        (60 * 60 * 24)  => 3,
        (60 * 60 * 40)  => 4,
        (60 * 60 * 60)  => 5,
        (60 * 60 * 100)  => 6,
    ];
}
