<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 5;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB    = 40;

    // the total number of items to process per request
    const MAX_ITEMS_PER_REQUEST    = 2;

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS  = 55;

    // the delay between sending requests and asking for request response data
    const CRONJOB_ASYNC_DELAY_MS   = 2750;

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
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        # Queue 1: Avg Sale Time < 1 hour
        (60 * 60 * 1)   => 1,

        # Queue 2: Avg Sale Time < 3 hour
        (60 * 60 * 3)   => 2,

        # Queue 3: Avg Sale Time < 12 hour
        (60 * 60 * 12)  => 3,

        # Queue 4: Avg Sale Time < 24 hour
        (60 * 60 * 24)  => 4,

        # Queue 5: Avg Sale Time < 40 hour (1.5 days)
        (60 * 60 * 40)  => 5,

        # Queue 6: Avg Sale Time < 72 hour (3 days)
        (60 * 60 * 72)  => 6,
    ];
}
