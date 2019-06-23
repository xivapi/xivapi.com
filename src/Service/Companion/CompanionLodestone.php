<?php

namespace App\Service\Companion;

use App\Common\Game\GameServers;
use App\Entity\CompanionCharacter;
use App\Repository\CompanionCharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lodestone\Api;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionLodestone
{
    const MAX_UPDATE = 500;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionCharacterRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(CompanionCharacter::class);
    }

    public function populate(int $offset = 0)
    {
        $start      = time();
        $date       = date('H:i:s');
        $console    = new ConsoleOutput();
        $characters = $this->repository->findBy(
            [ 'lodestoneId' => null, 'status' => 0 ],
            [ 'updated' => 'asc' ],
            self::MAX_UPDATE,
            self::MAX_UPDATE * $offset
        );
    
        $console->writeln(count($characters) ." characters - Start time: {$date}");
        $section = $console->section();
    
        $api = new Api();
    
        /** @var CompanionCharacter $character */
        foreach ($characters as $character) {
            if (time() - $start > 55) {
                $console->writeln("Ending due to time limit reached.");
                break;
            }
        
            $server = GameServers::LIST[$character->getServer()];
            $name   = $character->getName();
            $date   = date('H:i:s');
        
            $section->overwrite("[{$date}] {$name} - {$server}");
            $results = $api->searchCharacter($name, $server);

            // find character
            $found = false;
            if ($results->Pagination->ResultsTotal != 0) {
                foreach ($results->Results as $res) {
                    if ($res->Name == $name && $res->Server == $server) {
                        $character->setLodestoneId($res->ID)->setStatus(CompanionCharacter::STATUS_FOUND);
                        $found = true;
                        break;
                    }
                }
            }
    
            if ($found === false) {
                $section->overwrite('Character not found');
                $character
                    ->setStatus(CompanionCharacter::STATUS_NOT_FOUND)
                    ->setUpdated(time());
            }
    
            $this->em->persist($character);
            $this->em->flush();
        }
    }
}
