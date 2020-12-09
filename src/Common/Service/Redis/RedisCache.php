<?php

namespace App\Common\Service\Redis;

/**
 * Requires: https://github.com/phpredis/phpredis
 */
class RedisCache
{
    /** @var \Redis */
    private $instance;
    /** @var \Redis */
    private $pipeline;
    /** @var string */
    private $prefix = '';
    /** @var array */
    private $options = [
        'timeout'       => 3,
        'compression'   => 5,
        'default_time'  => 3600,
        'serializer'    => 0,
        'read_timeout'  => -1,
    ];
    
    public function connect(string $environment): RedisCache
    {
        $config = explode(',', getenv($environment));
        $ip     = $config[0];
        $port   = $config[1];
        $auth   = $config[2];
        $prefix = $config[3] ?? '';

        $this->instance = new \Redis();
        $this->instance->pconnect($ip, $port, $this->options['timeout']);
        $this->instance->auth($auth);
        $this->instance->setOption(\Redis::OPT_SERIALIZER, $this->options['serializer']);
        $this->instance->setOption(\Redis::OPT_READ_TIMEOUT, $this->options['read_timeout']);
        $this->prefix = $prefix;

        return $this;
    }
    
    public function disconnect()
    {
        $this->instance->close();
        $this->instance = null;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
    
    public function selectDatabase(int $index)
    {
        $this->instance->select($index);
        return $this;
    }
    
    public function startPipeline()
    {
        if ($this->pipeline) {
            throw new \Exception('Pipeline already initialized.');
        }
        
        $this->pipeline = $this->instance->multi(\Redis::PIPELINE);
    }
    
    public function executePipeline()
    {
        $this->pipeline->exec();
        $this->pipeline = null;
    }
    
    public function increment(string $key, int $amount = 1)
    {
        $this->instance->incrBy($key, $amount);
    }
    
    public function decrement(string $key, int $amount = 1)
    {
        $this->instance->decrBy($key, $amount);
    }
    
    public function set(string $key, $data, int $ttl = 3600, bool $serialize = false)
    {
        $data = ($this->options['serializer'] || $serialize)
            ? serialize($data)
            : gzcompress(json_encode($data), $this->options['compression']);
    
        if (json_last_error()) {
            throw new \Exception("COULD NOT SAVE TO REDIS, JSON ERROR: ". json_last_error_msg());
        }
    
        $this->pipeline ? $this->pipeline->set($key, $data, $ttl) : $this->instance->set($key, $data, $ttl);
    }
    
    public function setMulti(array $data, bool $serialize = false)
    {
        foreach ($data as $i => $d) {
            $data[$i] = ($this->options['serializer'] || $serialize)
                ? serialize($d)
                : gzcompress(json_encode($d), $this->options['compression']);
        }
        
        $this->pipeline ? $this->pipeline->mset($data) : $this->instance->mset($data);
    }
    
    public function setTimeout(string $key, int $ttl)
    {
        $this->instance->setTimeout($key, $ttl);
    }
    
    public function get(string $key, bool $serialize = false)
    {
        $data = $this->pipeline ? $this->pipeline->get($key) : $this->instance->get($key);
    
        if ($data) {
            $data = ($this->options['serializer'] || $serialize)
                ? unserialize($data)
                : json_decode(gzuncompress($data));
        }
        
        return $data;
    }
    
    public function getMulti(array $keys, bool $serialize = false)
    {
        $arr  = $this->pipeline ? $this->pipeline->mget($keys) : $this->instance->mget($keys);
        $data = [];
        
        if ($arr) {
            foreach ($arr as $item) {
                $data[] = ($this->options['serializer'] || $serialize)
                    ? unserialize($item)
                    : json_decode(gzuncompress($item));
            }
        }
        
        return $data;
    }
    
    public function getList(string $key)
    {
        return $this->instance->get($key);
    }
    
    public function getCount(string $key)
    {
        return $this->pipeline ? $this->pipeline->get($key) : $this->instance->get($key);
    }
    
    public function append(string $key, $value)
    {
        $this->pipeline ? $this->pipeline->rPush($key, $value) : $this->instance->rPush($key, $value);
    }
    
    public function delete(string $key)
    {
        $this->instance->delete($key);
    }
    
    public function keys(string $keys = '*')
    {
        return $this->instance->keys($keys);
    }
    
    public function flush()
    {
        $this->instance->flushDB();
    }
}
