<?php

namespace App\Command\Lodestone;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Entity\Linkshell;
use App\Entity\PvPTeam;
use App\Repository\CharacterAchievementRepository;
use App\Repository\CharacterFriendsRepository;
use App\Repository\CharacterRepository;
use App\Repository\FreeCompanyRepository;
use App\Repository\LinkshellRepository;
use App\Repository\PvPTeamRepository;
use App\Service\Lodestone\ServiceQueues;
use App\Service\LodestoneQueue\CharacterAchievementQueue;
use App\Service\LodestoneQueue\CharacterFriendQueue;
use App\Service\LodestoneQueue\CharacterQueue;
use App\Service\LodestoneQueue\FreeCompanyQueue;
use App\Service\LodestoneQueue\LinkshellQueue;
use App\Service\LodestoneQueue\PvPTeamQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This would run on a cronjob on XIVAPI
 */
class AutoManagerQueue extends Command
{
    /** @var SymfonyStyle */
    private $io;
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this->setName('AutoManagerQueue');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        
        if (date('i') == 30 || date('i') == 31) {
            $this->io->text('Skipping for Hypervisord restart.');
            return;
        }

        $this->queueCharacters();
        $this->queueFriendLists();
        $this->queueAchievements();
        $this->queueFreeCompanies();
        $this->queueLinkshells();
        $this->queuePvpTeams();
    }

    private function queueCharacters()
    {
        /** @var CharacterRepository $repo */
        $repo = $this->em->getRepository(Character::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_CHARACTER_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            CharacterQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "character_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            CharacterQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "character_update_{$number}_patreon");
        }
    }

    private function queueFriendLists()
    {
        /** @var CharacterFriendsRepository $repo */
        $repo = $this->em->getRepository(CharacterFriends::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_CHARACTER_FRIENDS_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            CharacterFriendQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "character_friends_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            CharacterFriendQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "character_friends_update_{$number}_patreon");
        }
    }

    private function queueAchievements()
    {
        /** @var CharacterAchievementRepository $repo */
        $repo = $this->em->getRepository(CharacterAchievements::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_CHARACTER_ACHIEVEMENTS_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            CharacterAchievementQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "character_achievements_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            CharacterAchievementQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "character_achievements_update_{$number}_patreon");
        }
    }

    private function queueFreeCompanies()
    {
        /** @var FreeCompanyRepository $repo */
        $repo = $this->em->getRepository(FreeCompany::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_FC_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            FreeCompanyQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "free_company_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            FreeCompanyQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "free_company_update_{$number}_patreon");
        }
    }

    private function queueLinkshells()
    {
        /** @var LinkshellRepository $repo */
        $repo = $this->em->getRepository(Linkshell::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_LS_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            LinkshellQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "linkshell_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            LinkshellQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "linkshell_update_{$number}_patreon");
        }
    }

    private function queuePvpTeams()
    {
        /** @var PvPTeamRepository $repo */
        $repo = $this->em->getRepository(PvPTeam::class);
        $this->io->text(__METHOD__);
    
        foreach(range(0, ServiceQueues::TOTAL_PVP_QUEUES) as $number) {
            $this->io->text("Queue: {$number}");
            PvPTeamQueue::queue($repo->getUpdateIds(Entity::PRIORITY_NORMAL, $number), "pvp_team_update_{$number}_normal");
        }
    
        foreach(range(0, ServiceQueues::TOTAL_QUEUES_PATRON) as $number) {
            $this->io->text("Queue Patreon: {$number}");
            PvPTeamQueue::queue($repo->getUpdateIds(Entity::PRIORITY_PATRON, $number), "pvp_team_update_{$number}_patreon");
        }
    }
}
