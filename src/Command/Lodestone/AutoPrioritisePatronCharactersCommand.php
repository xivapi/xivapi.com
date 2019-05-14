<?php

namespace App\Command\Lodestone;

use App\Common\Command\CommandConfigureTrait;
use App\Common\Constants\RedisConstants;
use App\Common\Entity\User;
use App\Common\Entity\UserCharacter;
use App\Common\Service\Redis\Redis;
use App\Common\User\Users;
use App\Entity\Character;
use App\Entity\Entity;
use App\Service\Lodestone\CharacterService;
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
    /** @var CharacterService */
    private $characterService;
    /** @var Users */
    private $users;

    public function __construct(
        EntityManagerInterface $em,
        CharacterService $characterService,
        Users $users,
        $name = null
    ) {
        parent::__construct($name);

        $this->em = $em;
        $this->users = $users;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api          = new XIVAPI();
        $userCharRepo = $this->em->getRepository(UserCharacter::class);
        $apiCharRepo  = $this->em->getRepository(Character::class);
        
        // User
        $patrons = $this->users->getPatrons();
        $patronsIgnored = [];

        //
        // Process patron characters
        //
        $output->writeln("Processing patrons");
        foreach ($patrons as $tier => $users) {
            /** @var User $user */
            foreach ($users as $user) {
                $characters = $userCharRepo->findBy(['user' => $user]);

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

                    // populate list to ignore (this is so friends don't alter current patron users)
                    $patronsIgnored[] = $character->getLodestoneId();

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

        //
        // Set friends
        //
        foreach ($patrons as $tier => $users) {
            /** @var User $user */
            foreach ($users as $user) {
                $characters = $userCharRepo->findBy(['user' => $user]);

                if (empty($characters)) {
                    continue;
                }

                /** @var UserCharacter $character */
                foreach ($characters as $character) {
                    /** @var Character $apiCharacter */
                    $apiCharacter = $apiCharRepo->findOneBy([
                        'id' => $character->getLodestoneId()
                    ]);

                    //
                    // Process patron friends
                    //
                    $key       = "lodestone_patron_updater_friends_{$character->getLodestoneId()}";
                    $existing  = Redis::cache()->get($key) ?: [];
                    $friends   = $this->characterService->getFriends($character->getLodestoneId())->data;
                    $friendIds = [];

                    // remove any characters processed from the friends list, this prevents
                    // someone being removed from a friend list who is already a patron
                    // supporter themselves, having their status revoked
                    foreach ($friends as $i => $friend) {
                        if (in_array($friend->ID, $patronsIgnored)) {
                            unset($friends[$i]);
                        }
                    }

                    // Mark all current friends as patron benefit
                    foreach ($friends as $friend) {
                        $friendIds[] = $friend->ID;

                        /** @var Character $apiFriend */
                        $apiFriend = $apiCharRepo->findOneBy([ 'id' => $friend->ID ]);

                        if ($apiFriend) {
                            $output->writeln("- ADD Friend: {$apiCharacter->getId()}");
                            $apiCharacter->setPriority(Entity::PRIORITY_PATRON);
                            $this->em->persist($apiCharacter);
                        }
                    }

                    // cache friends for the next run
                    Redis::cache()->set($key, $friendIds, RedisConstants::TIME_30_DAYS);

                    // find ids from existing friends list that are not in the new friends list
                    // if any are found, they will have their patron priority status removed.
                    $diff = array_diff($existing, $friendIds);
                    if ($diff) {
                        // remove patron status from deleted friends
                        foreach ($diff as $friendId) {
                            /** @var Character $apiFriend */
                            $apiFriend = $apiCharRepo->findOneBy([ 'id' => $friendId ]);

                            if ($apiFriend) {
                                $output->writeln("- REMOVE Friend: {$apiCharacter->getId()}");
                                $apiCharacter->setPriority(Entity::PRIORITY_NORMAL);
                                $this->em->persist($apiCharacter);
                            }
                        }
                    }

                    $this->em->flush();
                }
            }
        }

        $this->em->flush();
        $this->em->clear();
        
        $output->writeln("Done");
    }
}
