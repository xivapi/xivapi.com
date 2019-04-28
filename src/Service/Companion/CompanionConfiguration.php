<?php

namespace App\Service\Companion;

class CompanionConfiguration
{
    // If we hit this number of errors, the system will stop.
    const ERROR_COUNT_THRESHOLD = 2;

    // the total number of items to process per cronjob
    const MAX_ITEMS_PER_CRONJOB = 10;

    // the delay between requests
    const DELAY_BETWEEN_REQUESTS_MS = [500,500];
    
    // Minimum sales
    const MINIMUM_SALES_TO_UPDATE = 10;
    
    // Item never sold, or rarely sells
    const STATE_NEVER_SOLD = 8;
    
    // Item is new to the site
    const QUEUE_DEFAULT = 9;
    
    // Item is new to the site
    const QUEUE_NEW_ITEM = 10;

    // How long until exceptions are ignored.
    const EXCEPTION_TIMEOUT_SECONDS = (60 * 60);
    
    const QUEUE_INFO = [
        // name, consumers
        0  => 'Not Updating',
        
        1  => '< 2 hours',
        2  => '< 6 hours',
        3  => '< 24 hours',
        4  => '< 48 hours',
        
        5  => '(not used)',
        6  => '(not used)',
        7  => '(not used)',
        8  => '(not used)',
        9  => 'Default',
        10 => 'Item is new'
    ];
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        (60 * 60 * 2)   => 1,
        (60 * 60 * 6)   => 2,
        (60 * 60 * 24)  => 3,
    ];
    
    const QUEUE_CONSUMERS = [
        1,
        2,
        3,
        
        // 9
        self::QUEUE_DEFAULT,
    ];
    
    const QUEUE_CONSUMERS_PATREON = [
        10,
        11,
    ];
    
    // todo @deprecated
    // the total number of items to process per request
    const MAX_ITEMS_PER_REQUEST = 2;
    
    // todo @deprecated
    // the total time a cronjob should stay active
    const CRONJOB_TIMEOUT_SECONDS  = 55;
    
    // todo @deprecated
    // Delay pass time
    const DELAY_BETWEEN_REQUEST_RESPONSE = [5, 5];
}
