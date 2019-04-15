<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Service\Lodestone\CharacterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoFlagInactiveCharacters extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'AutoFlagInactiveCharacters',
        'desc' => 'Auto flags inactive characters if they have not had a request in some time.',
    ];

    /** @var CharacterService */
    private $characterService;

    public function __construct(CharacterService $characterService, $name = null)
    {
        $this->characterService = $characterService;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * php bin/console AutoFlagInactiveCharacters
         */
        $this->characterService->checkInactiveStatus();
    }
}
