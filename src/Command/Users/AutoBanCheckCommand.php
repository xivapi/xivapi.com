<?php

namespace App\Command\Users;

use App\Command\CommandHelperTrait;
use App\Entity\App;
use App\Entity\User;
use App\Service\Common\Mail;
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

        $apps = $this->em->getRepository(App::class)->findAll();

        // 30k hits in an hour
        $threshold = 30000;
        $bans = 0;

        /** @var App $app */
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
                $app->setApiRateLimit(0);
                $app->setLevel(1);
                $app->setRestricted(1);
                $app->setName("[BANNED] {$app->getName()}");

                $this->em->persist($app);
                $this->em->persist($user);

                $this->mail->send(
                    'josh@viion.co.uk',
                    "XIVAPI - Banned: {$app->getUser()->getUsername()} {$app->getApiKey()} {$app->getName()}",
                    "The App ID: {$app->getApiKey()} by {$app->getUser()->getUsername()} has automatically been banned for: {$count} requests in 1 hour."
                );
            }

            // reset
            Redis::Cache()->delete($key);
        }

        $this->em->flush();
        $this->io->text("Issued: {$bans} bans.");
    }
}
