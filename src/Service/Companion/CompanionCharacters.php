<?php

namespace App\Service\Companion;

use App\Entity\CompanionCharacter;
use App\Repository\CompanionCharacterRepository;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use XIVAPI\XIVAPI;

class CompanionCharacters
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var CompanionCharacterRepository */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(CompanionCharacter::class);
    }

    public function populate()
    {
        $console    = new ConsoleOutput();
        $characters = $this->repository->findBy([ 'server' => null ], [ 'added' => 'asc' ], 100);

        $console->writeln(count($characters) ." characters");
        $section = $console->section();

        $api = new XIVAPI();

        /** @var CompanionCharacter $character */
        foreach ($characters as $character) {
            $server = GameServers::LIST[$character->getServer()];
            $name   = $character->getName();

            $section->overwrite("{$name} - {$server}");
            $results = $api->character->search($name, $server);

            // found none
            if ($results->Pagination->ResultsTotal == 0) {
                continue;
            }

            // loop through
            foreach ($results->Results as $res) {
                if ($res->Name == $name && $res->Server == $server) {
                    $character->setLodestoneId($res->ID);
                    $this->em->persist($character);
                    break;
                }
            }
        }

        $this->em->flush();
    }
}
