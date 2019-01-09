<?php

namespace App\Command;

use App\Entity\App;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This script will run every 15 minutes and upgrade users and apps
 * from level 2 to level 3 after 1 hour, increasing their limits.
 */
class UpdateUserRestrictionsCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(?string $name = null, EntityManagerInterface $em)
    {
        parent::__construct($name);

        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('UpdateUserRestrictionsCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Updating user restrictions');

        //
        // Update Apps
        //

        $apps = $this->em->getRepository(App::class)->findBy([ 'level' => 2 ]);

        /** @var App $app */
        foreach ($apps as $app) {
            // if the app has been alive for over an hour and the user is not banned. Upgrade to level 3!
            if ($app->isLimited() === false && $app->getUser()->isBanned() === false) {
                $app->setLevel(3);
                $this->em->persist($app);
            }
        }

        //
        // Update users
        //

        $users = $this->em->getRepository(User::class)->findBy([ 'level' => 2, 'banned' => false ]);

        /** @var User $user */
        foreach ($users as $user) {
            // if the app has been alive for over an hour, upgrade to level 3!
            if ($user->isLimited() === false) {
                $user->setLevel(3);
                $this->em->persist($user);
            }
        }

        // save
        $this->em->flush();
    }
}
