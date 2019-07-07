<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 40;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 15;

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS = 55;
    
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
        2  => '< 3 hours',
        3  => '< 6 hours',
        4  => '< 12 hours',
        5  => '< 24 hours',
        6  => '< 30 hours',
        7  => '< 50 hours',
        8  => '< 72 hours',
        9  => '< 100 hours',

        50  => 'Never Sold',
        60  => 'Default',
        70  => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 1)   => 1,
        (60 * 60 * 3)   => 2,
        (60 * 60 * 6)   => 3,
        (60 * 60 * 12)  => 4,
        (60 * 60 * 24)  => 5,
        (60 * 60 * 30)  => 6,
        (60 * 60 * 50)  => 7,
        (60 * 60 * 72)  => 8,
        (60 * 60 * 100) => 9,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        50000,
        50001,
        50002,
        50003,
    ];
}
