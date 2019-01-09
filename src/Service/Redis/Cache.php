<?php

namespace App\Service\Redis;

use Redis;

/**
 * todo - this whole class can be static
 * Requires: https://github.com/phpredis/phpredis
 */
class Cache
{
    const TIMEOUT = 4;
    const COMPRESSION_LEVEL = 5;
    const DEFAULT_TIME = 3600;
    
    // options
    const OPTION_USE_SERIALIZER = 'useSerializer';

    /** @var \stdClass */
    public $config;
    /** @var Redis */
    public $instance = null;
    public $pipeline;

    private $options = [
        self::OPTION_USE_SERIALIZER => false,
    ];

    public function __construct()
    {
        if ($this->isCacheDisabled()) {
            return;
        }

        $this->options = (Object)$this->options;
    }

    /**
     * Get a redis auth from the env file
     */
    public function getConfig($name = null): \stdClass
    {
        if (!$name) {
            return $this->config;
        }
        
        [$ip, $port, $auth] = explode(',', getenv($name));

        return (Object)[
            'ip'   => $ip,
            'port' => $port,
            'auth' => $auth
        ];
    }

    /**
     * Set an option
     */
    public function setOption($option, $value)
    {
        $this->options->{$option} = $value;
    }

    /**
     * Connect to a server
     */
    public function connect(string $config, bool $force = false)
    {
        if ($this->isCacheDisabled()) {
            return null;
        }

        $config = (getenv('SITE_CONFIG_IS_LOCALHOST') && !$force) ? $this->getConfig('REDIS_SERVER_LOCAL') : $this->getConfig($config);
        $this->config = $config;
        
        // init Redis
        $this->instance = new Redis();
        $this->instance->pconnect($config->ip, $config->port, self::TIMEOUT);
        $this->instance->auth($config->auth);

        // options
        $this->instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $this->instance->setOption(Redis::OPT_READ_TIMEOUT, -1);
        return $this;
    }

    /**
     * Check redis is connected
     */
    public function checkConnection()
    {
        if ($this->instance === null) {
            $this->connect('REDIS_SERVER_LOCAL');
        }

        return $this;
    }

    /**
     * Start a pipeline
     */
    public function initPipeline()
    {
        $this->checkConnection();

        if ($this->pipeline) {
            throw new \Exception('You already have a pipeline excuted, did you mean execPipeline??');
        }

        if ($this->isCacheDisabled()) {
            return null;
        }

        $this->pipeline = $this->instance->multi(Redis::PIPELINE);
        return $this;
    }

    /**
     * Execute a pipeline
     */
    public function execPipeline()
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        $this->pipeline->exec();
        $this->pipeline = false;

        return $this;
    }
    
    /**
     * Increment an object
     */
    public function increment($key, $amount = 1)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        try {
            $this->instance->incrBy($key, $amount);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $this;
    }

    /**
     * Decrement an object
     */
    public function decrement($key, $amount = 1)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        try {
            $this->instance->decrBy($key, $amount);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $this;
    }

    /**
     * Set an object
     */
    public function set($key, $data, $ttl = self::DEFAULT_TIME, $serialize = false)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        try {
            $data = ($this->options->useSerializer || $serialize) ? serialize($data) : gzcompress(json_encode($data), self::COMPRESSION_LEVEL);

            if (json_last_error()) {
                throw new \Exception("COULD NOT SAVE TO REDIS, JSON ERROR: ". json_last_error_msg());
            }

            if (!$data){
                throw new \Exception('GZCompress Data is empty');
            }

            $this->pipeline ? $this->pipeline->set($key, $data, $ttl) : $this->instance->set($key, $data, $ttl);

        } catch (\Exception $ex) {
            throw $ex;
        }

        return $this;
    }

    /**
     * Set the timeout for a key
     */
    public function setTimeout($key, $ttl = self::DEFAULT_TIME)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        try {
            $this->instance->setTimeout($key, $ttl);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $this;
    }

    /**
     * Get object for key
     */
    public function get($key, $serialize = false)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }
        
        try {
            $data = $this->pipeline ? $this->pipeline->get($key) : $this->instance->get($key);

            if ($data) {
                $data = ($this->options->useSerializer || $serialize) ? unserialize($data) : json_decode(gzuncompress($data));
            }

            return $data;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Get a count (from doing increment/decrement)
     */
    public function getCount($key)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        try {
            return $this->pipeline ? $this->pipeline->get($key) : $this->instance->get($key);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Delete an object
     */
    public function delete($key)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        $this->instance->delete($key);
        return $this;
    }

    /**
     * Remove all objects based on a prefix
     */
    public function clear($prefix)
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        $count = 0;
        $keys = $this->instance->keys($prefix);

        foreach($keys as $key) {
            $this->delete($key);
            $count++;
        }

        return $count;
    }

    /**
     * Flush all objects
     */
    public function flush()
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        $this->instance->flushAll();
        return $this;
    }

    /**
     * Get Redis stats
     */
    public function stats()
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        return [
            'dbSize' => $this->instance ? $this->instance->dbSize() : 0,
            'info' => $this->instance ? $this->instance->info() : 0,
            'lastSave' => $this->instance ? $this->instance->lastSave() : 0,
        ];
    }

    /**
     * Get all keys
     */
    public function keys($prefix = '*')
    {
        $this->checkConnection();

        if ($this->isCacheDisabled()) {
            return null;
        }

        return $this->instance->keys($prefix);
    }

    /**
     * States if the redis cache is disabled
     */
    public function isCacheDisabled()
    {
        return getenv('REDIS_DISABLED');
    }
}
