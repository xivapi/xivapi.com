<?php

namespace App\Service\Companion;

class CompanionItemManagerPriorityTimes
{
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        // 30 minutes
        1800 => 10,

        // 1 hour
        3600 => 11,

        // 4 hours
        14400 => 12,

        // 6 hours
        21600 => 13,

        // 12 hours
        43200 => 14,

        // 18 hours
        64800 => 15,

        // 24 hours
        86400 => 16,

        // 30 hours
        108000 => 17,

        // 40 hours
        144000 => 18,

        // 60 hours
        216000 => 19,

        // 80 hours
        288000 => 20,

        // 100 hours
        360000 => 21,

        // 5 days
        432000 => 22,

        // 7 days
        604800 => 23,

        // 10 days
        864000 => 24,

        // 15 days
        1296000 => 25,

        // 20 days
        1728000 => 26,

        // 25 days
        2160000 => 27,

        // 30 days
        2592000 => 28,

        // 40 days
        3456000 => 29,

        // 50 days
        4320000 => 30,
    ];
}
