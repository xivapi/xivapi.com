<?php

namespace App\Command\Lodestone;

use App\Service\LodestoneQueue\RabbitMQ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This would run on a cronjob on XIVAPI
 */
class SetupChannels extends Command
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('SetupChannels');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channels = [
            'character_add',
            'character_update',
            'character_update_0_normal',
            'character_update_1_normal',
            'character_update_2_normal',
            'character_update_3_normal',
            'character_update_4_normal',
            'character_update_5_normal',
            'character_update_0_patreon',
            'character_update_1_patreon',
            'character_update_0_low',
            'character_update_1_low',
            
            'character_friends_add',
            'character_friends_update',
            'character_friends_update_0_normal',
            'character_friends_update_1_normal',
            'character_friends_update_0_patreon',
            'character_friends_update_1_patreon',
            
            'character_achievements_add',
            'character_achievements_update',
            'character_achievements_update_0_normal',
            'character_achievements_update_1_normal',
            'character_achievements_update_2_normal',
            'character_achievements_update_3_normal',
            'character_achievements_update_4_normal',
            'character_achievements_update_5_normal',
            'character_achievements_update_0_patreon',
            'character_achievements_update_1_patreon',
            
            'free_company_add',
            'free_company_update',
            'free_company_update_0_normal',
            'free_company_update_1_normal',
            'free_company_update_0_patron',
            'free_company_update_1_patron',
            
            'linkshell_add',
            'linkshell_update',
            'linkshell_update_0_normal',
            'linkshell_update_1_normal',
            'linkshell_update_0_patron',
            'linkshell_update_1_patron',
            
            'pvp_team_add',
            'pvp_team_update',
            'pvp_team_update_0_normal',
            'pvp_team_update_1_normal',
            'pvp_team_update_0_patron',
            'pvp_team_update_1_patron',
        ];
    
        $rabbit  = new RabbitMQ();
        $rabbit->connect()->getChannel();
        
        foreach ($channels as $channel) {
            $output->writeln('Creating channel: '. $channel);
            $rabbit->setQueue("{$channel}_request");
            $rabbit->setQueue("{$channel}_response");
        }

        $rabbit->close();
        $output->writeln('Complete');
    }
}
