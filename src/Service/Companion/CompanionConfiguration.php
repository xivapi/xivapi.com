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
        2  => '< 3 hour',
        3  => '< 8 hours',
        4  => '< 15 hours',
        5  => '< 24 hours',
        6  => '< 36 hours',
        7  => '< 48 hours',
        8  => '< 48 hours',
        9  => '< 48 hours',
        10 => '< 48 hours',
        11 => '< 48 hours',
        12 => '< 48 hours',
        13 => '< 48 hours',
        14 => '< 48 hours',
        15 => '< 48 hours',

        50  => 'Never Sold',
        60  => 'Default',
        70  => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 1)         => 1,
        (60 * 60 * 3)         => 2,
        (60 * 60 * 8)         => 3,
        (60 * 60 * 15)        => 4,
        (60 * 60 * 24)        => 5,
        (60 * 60 * 36)        => 6,
        (60 * 60 * 48)        => 7,
        (60 * 60 * 60)        => 8,
        (60 * 60 * 72)        => 9,
        (60 * 60 * 100)       => 10,
        (60 * 60 * 125)       => 11,
        (60 * 60 * 150)       => 12,
        (60 * 60 * 200)       => 13,
        (60 * 60 * 300)       => 14,
        (60 * 60 * 400)       => 15,

        // just give these 30 days to keep them at least somewhat updatable
        (60 * 60 * 24 * 30)   => self::STATE_NEVER_SOLD,
        (60 * 60 * 24 * 30)   => self::QUEUE_DEFAULT,
        (60 * 60 * 24 * 30)   => self::QUEUE_NEW_ITEM,
    ];

    const MAX_ITEMS_PER_QUEUE = [
        1  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        2  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        3  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        4  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        5  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        6  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        7  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        8  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        9  => (self::MAX_ITEMS_PER_CRONJOB * 5),
        10 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        11 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        12 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        13 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        14 => (self::MAX_ITEMS_PER_CRONJOB * 5),
        15 => (self::MAX_ITEMS_PER_CRONJOB * 5),

        50 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        60 => (self::MAX_ITEMS_PER_CRONJOB * 1),
        70 => (self::MAX_ITEMS_PER_CRONJOB * 1),
    ];
    
    const QUEUE_CONSUMERS = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,

        self::STATE_NEVER_SOLD,
        self::QUEUE_DEFAULT,
        self::QUEUE_NEW_ITEM,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        500,
        501,
        502,
        503
    ];
}
