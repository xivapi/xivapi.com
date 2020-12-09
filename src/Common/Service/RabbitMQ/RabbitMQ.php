<?php

namespace App\Common\Service\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Some rules:
 * - All messages MUST be JSON
 */
class RabbitMQ
{
    const CHANNEL = 1337;

    const QUEUE_OPTIONS = [
        'passive'       => false,
        'durable'       => true,
        'exclusive'     => false,
        'auto_delete'   => false,
        'nowait'        => false,
        'no_local'      => false,
        'no_ack'        => false,
    ];

    /** @var AMQPStreamConnection */
    private $connection;
    /** @var AMQPChannel */
    private $channel;
    /** @var AMQPChannel */
    private $channelAsync;
    /** @var string */
    private $queue;
    /** @var bool */
    private $queueDeclared = false;

    /**
     * Connect to a queue and return this class
     */
    public function connect(string $queue = null): RabbitMQ
    {
        $this->queue = $queue;
        $this->connection = new AMQPStreamConnection(
            getenv('API_RABBIT_IP'),
            getenv('API_RABBIT_PORT'),
            getenv('API_RABBIT_USERNAME'),
            getenv('API_RABBIT_PASSWORD'),
            '/',
            false,
            'AMQPLAIN',
            null,
            'en_US',
            120.0,
            120.0,
            null,
            true,
            60
        );

        return $this;
    }

    /**
     * Close the connection
     */
    public function close()
    {
        $this->connection ? $this->connection->close() : null;
        $this->channel ? $this->channel->close() : null;
        $this->channelAsync ? $this->channelAsync->close() : null;
    }

    /**
     * Read messages asynchronously, requires a class handler for processing messages
     * - If no messages are received in the "duration" period, the script will stop
     * - If the script loop continues past the "timeout" period, the script will stop
     *
     * @param $handler - Must be a callback function that will handle the JSON
     */
    public function readMessageAsync($handler)
    {
        /** @var AMQPChannel $channel */
        $this->channelAsync = $this->connection->channel();

        // callback function for message, use our handler callback
        $callback = function($message) use ($handler) {
            $handler(json_decode($message->body));
            $this->channelAsync->basic_ack($message->delivery_info['delivery_tag']);
        };

        // basic message consumer
        $this->channelAsync->basic_consume(
            $this->queue,
            'async_consumer',
            self::QUEUE_OPTIONS['no_local'],
            self::QUEUE_OPTIONS['no_ack'],
            self::QUEUE_OPTIONS['exclusive'],
            self::QUEUE_OPTIONS['nowait'],
            $callback
        );

        // process messages
        while(count($this->channelAsync->callbacks)) {
            $this->channelAsync->wait();
        }

        return;
    }

    /**
     * Read a message synchronously, this is slow
     */
    public function readMessageSync()
    {
        // grab message
        $message = $this->getChannel()->basic_get($this->queue);

        // register as confirmed
        $this->getChannel()->basic_ack($message->delivery_info['delivery_tag']);

        if (!$message) {
            return false;
        }

        // acknowledge the message
        return json_decode($message->body);
    }

    /**
     * Send a message to the queue
     */
    public function sendMessage($message)
    {
        $message = is_string($message) ? $message : json_encode($message);
        $this->getChannel()->basic_publish(new AMQPMessage($message), '', $this->queue);
        return $this;
    }

    /**
     * Batch submit a message
     */
    public function batchMessage($message)
    {
        $message = is_string($message) ? $message : json_encode($message);
        $this->getChannel()->batch_basic_publish(new AMQPMessage($message), '', $this->queue);
        return $this;
    }
    
    /**
     * Send a batch of messages
     */
    public function sendBatch()
    {
        $this->getChannel()->publish_batch();
        return $this;
    }

    /**
     * Get the current active channel
     */
    public function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->connection->channel(self::CHANNEL);
        }
        
        if (!$this->queueDeclared && $this->queue) {
            $this->setQueue();
        }

        return $this->channel;
    }
    
    public function setQueue(string $queue = null): RabbitMQ
    {
        $this->queueDeclared = true;
        $this->channel->queue_declare(
            $queue ? $queue : $this->queue,
            self::QUEUE_OPTIONS['passive'],
            self::QUEUE_OPTIONS['durable'],
            self::QUEUE_OPTIONS['exclusive'],
            self::QUEUE_OPTIONS['auto_delete'],
            self::QUEUE_OPTIONS['nowait']
        );
        
        return $this;
    }

    public function pingConnection()
    {
        if ($this->connection->isConnected() == false) {
            $this->connection->reconnect();
        }
    }
}
