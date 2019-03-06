<?php

namespace App\Service\Companion;

class CompanionItemManagerPriorityTimes
{
    const PRIORITY_TIMES_DEFAULT = 150;
    
    // Priority values against a slot of time
    const PRIORITY_TIMES = [
        // 3 hours
        (60 * 60 * 3)       => 100,
        // 24 hours
        (60 * 60 * 24)      => 120,
        // 3 days
        (60 * 60 * 24 * 3)  => 130,
    ];
}
