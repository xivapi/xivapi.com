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
        $thresholds = [
            // 200 a minute = 1000 in 5 minutes = 5/sec rate limit
            1000 => 5,
            // 400 a minute = 2000 in 5 minutes = 2/sec rate limit
            2000 => 2,
        ];

        /** @var UserApp $app */
        foreach($apps as $app) {
            $key   = "app_autolimit_count_{$app->getApiKey()}";
            $count = Redis::Cache()->getCount($key);

            // if count below 1000, we can ignore
            if ($count < 1000) {
                // if the user was auto rate limited before, return them to 5/5
                if ($app->isApiRateLimitAutoModified()) {
                    $app->rateLimits(5, 5)->setNotes("Rate limit has been auto modified back to 5/5 as user has not sent over 1000 requests in 5 minutes");

                    $this->em->persist($app);
                    $this->em->flush();
                }
                continue;
            }

            // loop through thresholds
            foreach ($thresholds as $requestLimit => $rateLimit) {
                if ($count > $requestLimit) {
                    Mog::send("<:status:474543481377783810> [XIVAPI] Auto-reduced Rate Limit of: {$app->getUser()->getUsername()} {$app->getApiKey()} {$app->getName()} - Requests in 5 minutes: {$count}");
                    $app->rateLimits($rateLimit, 1)
                        ->setApiRateLimitAutoModified(true)
                        ->setNotes("Rate limit has been reduced due to excessive use: {$count} requests in a 5 minute period.");
                }
            }

            $this->em->persist($app);
            Redis::Cache()->delete($key);
        }

        $this->em->flush();
    }
}
