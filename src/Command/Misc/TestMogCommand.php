<?php

namespace App\Command\Misc;

use App\Command\CommandHelperTrait;
use App\Service\Common\Mog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestMogCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('TestMogCommand')
            ->setDescription('Send a test message via mog')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Mog Test Message');

        $message = $this->io->ask("What message should I send?");
        $room = $this->io->ask("What room should I send to?", "ADMIN_MOG");


        Mog::send(trim($message), trim($room));
    }
}
