<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Companion\CompanionCharacters;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Companion_AutoPopulateCharactersCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'Companion_AutoPopulateCharactersCommand',
        'desc' => 'Goes through the companion characters table and finds the lodestone id for each character.',
    ];

    /** @var CompanionCharacters */
    private $companionCharacters;

    public function __construct(CompanionCharacters $companionCharacters, $name = null)
    {
        $this->companionCharacters = $companionCharacters;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->companionCharacters->populate();
    }
}
