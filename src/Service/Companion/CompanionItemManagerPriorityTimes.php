<?php

namespace App\Service\Companion;

class CompanionItemManagerPriorityTimes
{
    const PRIORITY_TIMES_DEFAULT = 150;
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        // 2 hours
        (60 * 60 * 2) => 100,
        // 6 hours
        (60 * 60 * 6) => 110,
        // 24 hours
        (60 * 60 * 24) => 120,
        // 3 days
        (60 * 60 * 24 * 3) => 130,
        // 7 days
        (60 * 60 * 24 * 3) => 140,
    ];
}
