<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 5;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 15;

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS = 56;
    
    // Minimum sales
    const MINIMUM_SALES_TO_UPDATE = 5;
    
    // Item never sold, or rarely sells
    const QUEUE_NOT_UPDATING = 0;
    
    // Item never sold, or rarely sells
    const QUEUE_NEVER_SOLD = 50;
    
    // Item is new to the site
    const QUEUE_DEFAULT = 60;
    
    // Item is new to the site
    const QUEUE_NEW_ITEM = 70;
    
    // Queue information
    const QUEUE_INFO = [
        0  => 'Not Updating',
        
        1  => '< 1 hour',
        2  => '< 5 hours',
        3  => '< 10 hours',
        4  => '< 24 hours',
        5  => '< 168 hours',
        6  => '< 1000 hours',

        50  => 'Never Sold',
        60  => 'Default',
        70  => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 1)     => 1,
        (60 * 60 * 5)     => 2,
        (60 * 60 * 10)    => 3,
        (60 * 60 * 25)    => 4,
        (60 * 60 * 168)   => 5,
        (60 * 60 * 1000)  => 6,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        50000,
        50001,
        50002,
    ];

    const QUEUE_CONSUMERS_MANUAL = [
        55000,
        55001,
        55002,
        55003,
    ];
}
