<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 5;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 15;

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS = 58;
    
    // Minimum sales
    const MINIMUM_SALES_TO_UPDATE = 3;
    
    // Item never sold, or rarely sells
    const QUEUE_NOT_UPDATING = 0;
    
    // Item never sold, or rarely sells
    const QUEUE_NEVER_SOLD = 3;
    
    // Item is new to the site
    const QUEUE_DEFAULT = 2;
    
    // Item is new to the site
    const QUEUE_NEW_ITEM = 5;
    
    // Queue information
    const QUEUE_INFO = [
        0  => 'Not Updating',
        // timed queues
        1  => 'Common Sellers',
        2  => 'Default',
        3  => 'Never Sold',
        4  => 'Low Traffic',
        5  => 'New Item'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 3)     => 1,
        (60 * 60 * 99999) => 2,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        50000,
        50001,
        50002,
        50003,
        50004,
    ];

    const QUEUE_CONSUMERS_MANUAL = [
        55000,
        55001,
        55002,
        55003,
        55004,
    ];
}
