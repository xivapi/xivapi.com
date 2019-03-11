<?php

namespace App\Service\Companion;

class CompanionItemManagerPriorityTimes
{
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
