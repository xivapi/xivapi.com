<?php

namespace App\Command\Users;

use App\Command\CommandHelperTrait;
use App\Entity\UserApp;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManageUserRestrictionsCommand extends Command
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

        $filters = [
            'new' => true,
            'locked' => false,
            'banned' => false,
        ];

        $apps  = $this->em->getRepository(UserApp::class)->findBy($filters);
        $users = $this->em->getRepository(User::class)->findBy($filters);

        // 2 hour timeout for new accounts
        $newTimeout     = time() - (60*60*2);

        // further timeout thresholds
        $oneDayTimeout  = time() - (60*60*24*1);
        $oneWeekTimeout = time() - (60*60*24*7);

        /**
         * Manage new app and their rate limits + new status
         */

        /** @var UserApp $app */
        foreach ($apps as $app) {
            // if app was added less than 2 hours ago, skip
            if ($app->getAdded() > $newTimeout) {
                continue;
            }

            // remove new status
            $app->setNew(false);

            // increase rate limit to 3 + burst 2
            $app->rateLimits(3, 2);

            // after 1 day, increase limit to 5 + burst 5
            if ($app->getAdded() > $oneDayTimeout) {
                $app->rateLimits(5, 5);
            }

            // after 1 week, increase to 10 + burst 10.
            if ($app->getAdded() > $oneWeekTimeout) {
                $app->rateLimits(10, 10);
            }

            // save
            $this->em->persist($app);
        }

        /**
         * Manage new users and their max apps
         */

        /** @var User $user */
        foreach ($users as $user) {
            // if user was added less than 2 hours ago, skip
            if ($user->getAdded() > $newTimeout) {
                continue;
            }

            // remove new status
            $user->setNew(false);

            // after 1 day, increase app limit to 3
            if ($user->getAdded() > $oneDayTimeout) {
                $user->setAppsMax(3);
            }

            // after 1 week, increase app limit to 10
            if ($user->getAdded() > $oneDayTimeout) {
                $user->setAppsMax(10);
            }

            // save
            $this->em->persist($user);
        }

        // save
        $this->em->flush();
    }
}
