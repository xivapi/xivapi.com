<?php

namespace App\Command\Users;

use App\Command\CommandHelperTrait;
use App\Entity\UserApp;
use App\Service\Common\Mail;
use App\Service\Common\Mog;
use App\Service\Redis\Redis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * todo - Add a rate limit count to the user, if they hit their rate limit often then ban their account after X resets
 */
class AutoRateLimitCheckCommand extends Command
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
        $this->setName('AutoRateLimitCheckCommand');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->text('Running auto rate limit check');

        $apps = $this->em->getRepository(UserApp::class)->findBy([
            'new'    => false,
            'locked' => false,
            'banned' => false,
        ]);

        $bans    = 0;
        $timeout = time() - (60 * 60 * 24);

        /** @var UserApp $app */
        foreach($apps as $app) {
            // grab count and reset count.
            $count = Redis::Cache()->getCount("app_autolimit_count_{$app->getApiKey()}");
            Redis::Cache()->delete("app_autolimit_count_{$app->getApiKey()}");

            // App has done less than 1000 requests
            // and has been previously rate limited
            // and it has been over 24 hours
            // restore back to 10/10
            if ($count < 1000 &&
                $app->isApiRateLimitAutoModified() &&
                $app->getApiRateLimitAutoModifiedDate() < $timeout
            ) {
                $app->rateLimits(10, 10)
                    ->setApiRateLimitAutoModified(false)
                    ->setApiRateLimitAutoModifiedDate(time())
                    ->setNotes("Rate Limit has been restored.");

                $this->em->persist($app);
                $this->em->flush();
            }

            // if the user requests are below 2000, skip
            if ($count < 2000) {
                continue;
            }

            if ($count > 2000) {
                $app->rateLimits(2, 1)
                    ->setApiRateLimitAutoModified(false)
                    ->setApiRateLimitAutoModifiedDate(time())
                    ->setNotes("Rate Limit was automatically reduce due to spam detection.");

                $this->em->persist($app);
                $this->em->flush();

                $message = "<:status:474543481377783810> [XIVAPI] - RATE LIMIT REDUCTION";
                $message .= " - **{$app->getUser()->getUsername()}** `{$app->getApiKey()}`, App Name: {$app->getName()}";
                $message .= " - Requests: {$count}. Rate limit reduced to 2 with 1 burst.";

                Mog::send($message);
            }
        }

        $this->em->flush();
        $this->io->text("Issued: {$bans} bans.");
    }
}
