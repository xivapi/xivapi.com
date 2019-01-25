<?php

namespace App\Command\Users;

use App\Command\CommandHelperTrait;
use App\Entity\UserApp;
use App\Entity\User;
use App\Service\Common\Mail;
use App\Service\Common\Mog;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoBanCheckCommand extends Command
{
    use CommandHelperTrait;

    /** @var EntityManagerInterface */
    private $em;
    /** @var Mail */
    private $mail;

    public function __construct(EntityManagerInterface $em, Mail $mail, ?string $name = null)
    {
        $this->em = $em;
        $this->mail = $mail;

        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this->setName('AutoBanCheckCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Running auto ban check');

        $apps = $this->em->getRepository(UserApp::class)->findAll();

        // requests threshold in 1 hour to auto ban
        $threshold = 15000;
        $bans = 0;

        /** @var UserApp $app */
        foreach($apps as $app) {
            $key   = "app_autoban_count_{$app->getApiKey()}";
            $count = Redis::Cache()->getCount($key);

            // if count below 1000, ignore
            if ($count < 1000) {
                continue;
            }

            $this->io->text("{$count} requests by: {$app->getName()} <comment>{$app->getApiKey()}</comment>");

            if ($count > $threshold) {
                $bans++;

                /** @var User $user */
                $user = $app->getUser();
                $user->setBanned(true);
                $user->setAppsMax(0);
                $user->setNotes("Auto banned for: {$count} requests within 1 hour.");

                // reduce all other apps down to 0
                foreach ($user->getApps() as $userApp) {
                    $userApp->rateLimits(0,0)->setBanned(true);
                    $this->em->persist($userApp);
                }

                $this->em->persist($user);

                $subject = "XIVAPI - Banned: {$app->getUser()->getUsername()}";
                $message = "Auto-Banned: {$app->getUser()->getUsername()} {$app->getApiKey()} {$app->getName()} for: {$count} api requests in 1 hour.";
                $this->mail->send('josh@viion.co.uk', $subject, $message);
                Mog::send("<:status:474543481377783810> [XIVAPI] ". $message);
            }

            Redis::Cache()->delete($key);
        }

        $this->em->flush();
        $this->io->text("Issued: {$bans} bans.");
    }
}
