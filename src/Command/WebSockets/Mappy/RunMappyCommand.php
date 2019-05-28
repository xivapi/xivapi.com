<?php

namespace App\Command\WebSockets;

use App\Service\Maps\MappyWebsocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class RunMappyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('RunMappyCommand')
            ->setDescription('Run the mappy import socket')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Prepping server...");
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new MappyWebsocket()
                )
            ),
            8080
        );
    
        $output->writeln("Running server");
        $server->run();
    }
}
