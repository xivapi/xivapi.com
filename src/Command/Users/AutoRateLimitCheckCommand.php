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

        $filters = [
            'new'    => false,
            'locked' => false,
            'banned' => false,
        ];

        $apps = $this->em->getRepository(UserApp::class)->findBy($filters);

        // requests threshold in 5 minute period to monitor
        // bursts will go down to 1
        $bans = 0;
        $thresholds = [
            // 5 requests a second for 5 minutes consecutively.
            1500 => 5,
            // 8 requests a second for 5 minutes consecutively
            2400 => 2,
            // 12 requests a second for 5 minutes consecutively
            3600 => 1,
            // 15 requests a second for 5 minutes consecutively
            4500 => 0,
        ];
    
        // timeouts for rate limits
        $twoHourTimeout = time() - (60*60*2);
        $oneDayTimeout  = time() - (60*60*24*1);
        $oneWeekTimeout = time() - (60*60*24*7);

        /** @var UserApp $app */
        foreach($apps as $app) {
            $key   = "app_autolimit_count_{$app->getApiKey()}";
            $count = Redis::Cache()->getCount($key);

            // if count below 1000, we can ignore
            if ($count < 1000) {
                // if the user was auto rate limited before, return them to 5/5
                if ($app->isApiRateLimitAutoModified()) {
                    $app->rateLimits(1, 1);
    
                    if ($app->getApiRateLimitAutoModifiedDate() < $twoHourTimeout) {
                        $app->rateLimits(3, 2);
                    } else if ($app->getApiRateLimitAutoModifiedDate() < $oneDayTimeout) {
                        $app->rateLimits(5, 5);
                    } else if ($app->getApiRateLimitAutoModifiedDate() < $oneWeekTimeout) {
                        $app->rateLimits(10, 10);
                    }
                    
                    $app->setApiRateLimitAutoModifiedDate(time())
                        ->setNotes("Rate limit has been automatically increased to a soft limit.");

                    $this->em->persist($app);
                    $this->em->flush();
                }
                continue;
            }

            // loop through thresholds
            $limit = false;
            foreach ($thresholds as $requestLimit => $rateLimit) {
                if ($count > $requestLimit) {
                    $limit = $rateLimit;
                    $bans++;
                }
            }
            
            if ($limit) {
                Mog::send("<:status:474543481377783810> [XIVAPI] Auto-reduced ratelimit to `{$limit}` for: **{$app->getUser()->getUsername()}** `{$app->getApiKey()}`, App Name: {$app->getName()} - Requests in 5 minutes: {$count}");
                
                $app->rateLimits($limit, 1)
                    ->setApiRateLimitAutoModified(true)
                    ->setNotes("Rate limit has been reduced to: {$limit}/sec due to excessive use: {$count} requests in a 5 minute period.");
            }
            
            $this->em->persist($app);
            Redis::Cache()->delete($key);
        }

        $this->em->flush();
        $this->io->text("Issued: {$bans} bans.");
    }
}
