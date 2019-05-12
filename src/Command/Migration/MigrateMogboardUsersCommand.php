<?php

namespace App\Command\Migration;

use App\Common\Command\CommandConfigureTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMogboardUsersCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'MigrateMogboardUsersCommand',
        'desc' => '',
    ];
    
    private $em;
    
    public function __construct(EntityManagerInterface $em, ?string $name = null)
    {
        parent::__construct($name);
        
        $this->em = $em;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // todo -   migrate mogboard users to xivapi, overwrite mogboard user ids over the xivapi user ids as
        // todo -   they have links to other stuff (lists, alerts, etc)
        // todo -   generate api keys for all the users
    }
}
