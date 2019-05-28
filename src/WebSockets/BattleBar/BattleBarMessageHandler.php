<?php

namespace App\WebSockets\BattleBar;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class BattleBarMessageHandler implements MessageComponentInterface
{
    /** @var ConsoleOutput */
    protected $console;

    public function __construct()
    {
        $this->console = new ConsoleOutput();
    }

    /**
     * A new connection opens to a new client
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $client)
    {
        Clients::add($client);
    }

    /**
     * Message FROM the client
     */
    public function onMessage(ConnectionInterface $clientFrom, $message)
    {
        try {
            CommandHandler::handle($clientFrom, $message);
        } catch (\Exception $ex) {
            $this->console->writeln("Custom Error: {$ex->getMessage()}");
        }
    }

    /**
     * Client disconnects.
     */
    public function onClose(ConnectionInterface $client)
    {
        Clients::remove($client);
    }

    /**
     * On WebSocket server error
     */
    public function onError(ConnectionInterface $conn, \Exception $ex)
    {
        $this->console->writeln("Socket Error: {$ex->getMessage()}");
        $this->console->writeln("Closing connection to client...");
        $conn->close();
    }
}
