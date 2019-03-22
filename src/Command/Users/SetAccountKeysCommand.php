<?php

namespace App\Command\Users;

use App\Command\CommandConfigureTrait;
use App\Entity\User;
use App\Service\User\Users;
use App\Utils\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetAccountKeysCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'SetAccountKeysCommand',
        'desc' => 'Setup the account keys for all users if they do not have one',
    ];

    /** @var Users */
    private $users;

    public function __construct(Users $users, $name = null)
    {
        $this->users = $users;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->users->getRepository()->findAll();

        /** @var User $user */
        foreach ($users as $user) {
            if (empty($user->getApiPublicKey())) {
                $user->setApiPublicKey(Random::randomAccessKey());
                $this->users->save($user);
            }
        }
    }
}
