<?php

namespace App\Command\Lodestone;

use App\Common\Command\CommandConfigureTrait;
use App\Common\Entity\User;
use App\Common\Entity\UserCharacter;
use App\Common\User\Users;
use App\Entity\Character;
use App\Entity\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XIVAPI\XIVAPI;

class AutoPrioritisePatronCharactersCommand extends Command
{
    use CommandConfigureTrait;
    
    const COMMAND = [
        'name' => 'AutoPrioritisePatronCharactersCommand',
        'desc' => 'Move all patron member characters to the patron queue.',
    ];

    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;

    public function __construct(EntityManagerInterface $em, Users $users, $name = null)
    {
        parent::__construct($name);

        $this->em = $em;
        $this->users = $users;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api         = new XIVAPI();
        $charRepo    = $this->em->getRepository(UserCharacter::class);
        $apiCharRepo = $this->em->getRepository(Character::class);
        
        // User
        $patrons = $this->users->getPatrons();
        
        $output->writeln("Processing patrons");
        foreach ($patrons as $tier => $users) {
            /** @var User $user */
            foreach ($users as $user) {
                $characters = $charRepo->findBy([ 'user' => $user ]);
                
                if (empty($characters)) {
                    continue;
                }
                
                $output->writeln("User: {$user->getUsername()}");
                
                // Move to patron queue
                /** @var UserCharacter $character */
                foreach ($characters as $character) {
                    /** @var Character $apiCharacter */
                    $apiCharacter = $apiCharRepo->findOneBy([
                        'id' => $character->getLodestoneId()
                    ]);
                    
                    // if already set, skip
                    if ($apiCharacter->getPriority() == Entity::PRIORITY_PATRON) {
                        continue;
                    }
                    
                    // if it does not exist, it needs adding, then next iteration it should exist
                    // and get added to patron queue
                    if ($apiCharacter === null) {
                        $api->character->get($character->getLodestoneId());
                        continue;
                    }
    
                    $output->writeln("- {$apiCharacter->getId()}");
                    $apiCharacter->setPriority(Entity::PRIORITY_PATRON);
                    $this->em->persist($apiCharacter);
                    $this->em->flush();
                }
            }
        }
    
        $this->em->flush();
        $this->em->clear();
        
        $output->writeln("Done");
    }
}
