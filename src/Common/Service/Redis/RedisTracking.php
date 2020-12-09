<?php

namespace App\Common\Service\Redis;

class RedisTracking
{
    /**
     * Track a stat
     */
    public static function track(string $constant, $value)
    {
        $tracking = Redis::Cache()->get('mb_tracking') ?: (Object)[];
        $tracking->{$constant} = $value;
        
        Redis::Cache()->set("mb_tracking", $tracking, 3600 * 24);
    }
    
    /**
     * Increment a stat
     */
    public static function increment(string $constant)
    {
        $tracking = Redis::Cache()->get('mb_tracking') ?: (Object)[];
        $tracking->{$constant} = isset($tracking->{$constant}) ? $tracking->{$constant} + 1 : 1;
        
        Redis::Cache()->set("mb_tracking", $tracking, 3600 * 24);
    }
    
    /**
     * Append a stat
     */
    public static function append(string $constant, $value)
    {
        $constant = $constant . '_LIST';
        $tracking = Redis::Cache()->get('mb_tracking') ?: (Object)[];
        
        $tracking->{$constant} = isset($tracking->{$constant}) ? $tracking->{$constant} : [];
        $tracking->{$constant}[] = $value;
        
        Redis::Cache()->set("mb_tracking", $tracking, 3600 * 24);
    }

    /**
     * Delete a stat
     */
    public static function delete(string $constant)
    {
        $tracking = Redis::Cache()->get('mb_tracking') ?: (Object)[];
        unset($tracking->{$constant});

        Redis::Cache()->set("mb_tracking", $tracking, 3600 * 24);
    }
    
    /**
     * Get all tracking stats
     */
    public static function get()
    {
        return Redis::Cache()->get('mb_tracking');
    }
    
    /**
     * Reset all tracking stats or a single one passed in
     */
    public static function reset()
    {
        Redis::Cache()->delete('mb_tracking');
    }
}
