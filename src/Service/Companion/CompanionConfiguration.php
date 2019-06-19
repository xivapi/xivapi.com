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
    const STATE_NEVER_SOLD = 50;
    
    // Item is new to the site
    const QUEUE_DEFAULT = 60;
    
    // Item is new to the site
    const QUEUE_NEW_ITEM = 70;
    
    const QUEUE_INFO = [
        // name, consumers
        0  => 'Not Updating',
        
        1  => '< 1 hour',
        2  => '< 2 hours',
        3  => '< 3 hours',
        4  => '< 4 hours',
        5  => '< 6 hours',
        6  => '< 12 hours',
        7  => '< 16 hours',
        8  => '< 24 hours',
        9  => '< 30 hours',
        10 => '< 40 hours',
        11 => '< 60 hours',
        12 => '< 100 hours',
        13 => '< 200 hours',
        14 => '< 300 hours',
        15 => '< 400 hours',

        50  => 'Never Sold',
        60  => 'Default',
        70  => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 1)         => 1,
        (60 * 60 * 2)         => 2,
        (60 * 60 * 3)         => 3,
        (60 * 60 * 4)         => 4,
        (60 * 60 * 6)         => 5,
        (60 * 60 * 12)        => 6,
        (60 * 60 * 16)        => 7,
        (60 * 60 * 24)        => 8,
        (60 * 60 * 30)        => 9,
        (60 * 60 * 40)        => 10,
        (60 * 60 * 60)        => 11,
        (60 * 60 * 100)       => 12,
        (60 * 60 * 200)       => 13,
        (60 * 60 * 300)       => 14,
        (60 * 60 * 400)       => 15,

        // just give these 30 days to keep them at least somewhat updatable
        (60 * 60 * 24 * 30)   => self::STATE_NEVER_SOLD,
        (60 * 60 * 24 * 30)   => self::QUEUE_DEFAULT,
        (60 * 60 * 24 * 30)   => self::QUEUE_NEW_ITEM,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        50000,
        50001,
        50002,
        50003
    ];
}
