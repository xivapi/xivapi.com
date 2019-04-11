<?php

namespace App\Command\Companion;

use App\Command\CommandConfigureTrait;
use App\Entity\CompanionMarketItemEntry;
use App\Service\Companion\CompanionItemManager;
use App\Service\Content\GameServers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Temp_AddRegionToItems extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'Temp_AddRegionToItems',
        'desc' => 'TEMP',
    ];

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em, $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jp = GameServers::LIST_DC['Elemental'] + GameServers::LIST_DC['Gaia'] + GameServers::LIST_DC['Mana'];
        $na = GameServers::LIST_DC['Aether'] + GameServers::LIST_DC['Primal'] + GameServers::LIST_DC['Crystal'];
        $eu = GameServers::LIST_DC['Chaos'] + GameServers::LIST_DC['Light'];

        foreach ($jp as $i => $serverName) {
            $jp[$i] = GameServers::getServerId($serverName);
        }

        foreach ($na as $i => $serverName) {
            $na[$i] = GameServers::getServerId($serverName);
        }

        foreach ($eu as $i => $serverName) {
            $eu[$i] = GameServers::getServerId($serverName);
        }

        $sql1 = "UPDATE companion_market_item_entry SET region = 1 WHERE server IN (". implode(',', $jp) .")";
        $sql2 = "UPDATE companion_market_item_entry SET region = 2 WHERE server IN (". implode(',', $na) .")";
        $sql3 = "UPDATE companion_market_item_entry SET region = 3 WHERE server IN (". implode(',', $eu) .")";

        print_r([
            $sql1,
            $sql2,
            $sql3
        ]);
    }
}
