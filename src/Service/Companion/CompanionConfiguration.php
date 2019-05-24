<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 20;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 15;

    // the total number of items to queue per minute
    // this would be max per cronjob * number of scripts
    const MAX_ITEMS_TOTAL = (self::MAX_ITEMS_PER_CRONJOB * 5);

    const MAX_ITEMS_PER_QUEUE = [
        1 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        2 => (self::MAX_ITEMS_PER_CRONJOB * 4),
        3 => (self::MAX_ITEMS_PER_CRONJOB * 2),
        4 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        5 => (self::MAX_ITEMS_PER_CRONJOB * 1),
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
        5  => '< 48 hours',
        
        6  => '(not used)',
        7  => '(not used)',
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
        (60 * 60 * 24 * 365)  => 8,
        (60 * 60 * 24 * 365)  => 9,
    ];
    
    const QUEUE_CONSUMERS = [
        1,
        2,
        3,
        4,
        5,

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
