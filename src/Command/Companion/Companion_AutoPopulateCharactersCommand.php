<?php

namespace App\Command\Companion;

use App\Common\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionLodestone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoPopulateCharactersCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoPopulateCharactersCommand',
        'desc' => 'Goes through the companion characters table and finds the lodestone id for each character.',
        'args' => [
            [ 'offset', InputArgument::OPTIONAL, 'offset' ]
        ]
    ];

    /** @var CompanionLodestone */
    private $companionLodestone;

    public function __construct(CompanionLodestone $companionLodestone, $name = null)
    {
        $this->companionLodestone = $companionLodestone;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionLodestone->populate(
            $input->getArgument('offset')
        );
    }
}
