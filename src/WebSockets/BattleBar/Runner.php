<?php

namespace App\WebSockets\BattleBar;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Output\ConsoleOutput;

class Runner
{
    const PORT = 8080;

    /**
     * Start the battle bar runner
     */
     public function start()
     {
         $console = new ConsoleOutput();
         $console->writeln("[Battle Bar] Initializing");

         // setup everything to run the websocket server
         $handler    = new MessageHandler();
         $wsServer   = new WsServer($handler);
         $httpServer = new HttpServer($wsServer);
         $server     = IoServer::factory($httpServer, self::PORT);

         $console->writeln("[Battle Bar] Starting Runner");
         $server->run();
     }
}
