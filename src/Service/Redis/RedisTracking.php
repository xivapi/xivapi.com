<?php

namespace App\Service\Redis;

class RedisTracking
{
    const TEST                       = 'TEST';
    const STARTDATE                  = 'STARTDATE';
    
    const TOTAL_ALERTS               = 'TOTAL_ALERTS';
    const TOTAL_ALERTS_DPS           = 'TOTAL_ALERTS_DPS';
    const TOTAL_ALERTS_TRIGGERED     = 'TOTAL_ALERTS_DPS_ACCEPTED';
    const TOTAL_MANUAL_UPDATES       = 'TOTAL_MANUAL_UPDATES';
    const TOTAL_MANUAL_UPDATES_FORCE = 'TOTAL_MANUAL_UPDATES_FORCE';
    
    const ITEM_UPDATED               = 'ITEM_UPDATED';
    const PAGE_VIEW                  = 'PAGE_VIEW';
    
    const TRACKING = [
        self::TEST,
        self::STARTDATE,
        self::TOTAL_ALERTS,
        self::TOTAL_ALERTS_DPS,
        self::TOTAL_ALERTS_TRIGGERED,
        self::TOTAL_MANUAL_UPDATES,
        self::TOTAL_MANUAL_UPDATES_FORCE,
        self::ITEM_UPDATED,
        self::PAGE_VIEW,
    ];
    
    /**
     * Track a stat
     */
    public static function track(string $constant, $value)
    {
        if (Redis::Cache()->get('mb_tracking_STARTDATE') == null) {
            Redis::Cache()->set("mb_tracking_STARTDATE", date('Y-m-d H:i:s'), (60 * 60 * 168));
        }
        
        Redis::Cache()->set("mb_tracking_{$constant}", $value);
    }
    
    /**
     * Increment a stat
     */
    public static function increment(string $constant, $value = 1)
    {
        Redis::Cache()->increment("mb_tracking_{$constant}", $value);
    }
    
    /**
     * Get all tracking stats
     */
    public static function get()
    {
        $results = [];
        
        foreach (self::TRACKING as $constant) {
            $results[$constant] = Redis::Cache()->getCount("mb_tracking_{$constant}");
        }
        
        return $results;
    }
    
    /**
     * Reset all tracking stats or a single one passed in
     */
    public static function reset(?string $constant = null)
    {
        if ($constant) {
            Redis::Cache()->delete("mb_tracking_{$constant}");
            return;
        }
        
        foreach (self::TRACKING as $constant) {
            Redis::Cache()->delete("mb_tracking_{$constant}");
        }
    }
}
