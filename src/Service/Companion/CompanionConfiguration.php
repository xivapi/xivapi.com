<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 40;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 15;

    const MAX_ITEMS_PER_QUEUE = [
        1 => (self::MAX_ITEMS_PER_CRONJOB * 6),
        2 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        3 => (self::MAX_ITEMS_PER_CRONJOB * 2),
        4 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        5 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        6 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        7 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        
        8 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        9 => (self::MAX_ITEMS_PER_CRONJOB * 1)
    ];

    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS = 55;
    
    // Minimum sales
    const MINIMUM_SALES_TO_UPDATE = 5;
    
    // Item never sold, or rarely sells
    const STATE_NEVER_SOLD = 8;
    
    // Item is new to the site
    const QUEUE_DEFAULT = 9;
    
    // Item is new to the site
    const QUEUE_NEW_ITEM = 10;
    
    const QUEUE_INFO = [
        // name, consumers
        0  => 'Not Updating',
        
        1  => '< 1 hour',
        2  => '< 3 hour',
        3  => '< 12 hours',
        4  => '< 30 hours',
        5  => '< 2 days',
        6  => '< 7 days',
        7  => '< 14 days',
        
        8  => 'Never Sold',
        9  => 'Default',
        10 => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 1)         => 1,
        (60 * 60 * 3)         => 2,
        (60 * 60 * 12)        => 3,
        (60 * 60 * 30)        => 4,
        (60 * 60 * 48)        => 5,
        (60 * 60 * 168)       => 6,
        (60 * 60 * 336)       => 7,
        
        (60 * 60 * 24 * 30)   => 8,
        (60 * 60 * 24 * 30)   => 9,
    ];
    
    const QUEUE_CONSUMERS = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,

        // 8
        self::STATE_NEVER_SOLD,

        // 9
        self::QUEUE_DEFAULT,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        10,
        11,
        12,
        13
    ];
}
