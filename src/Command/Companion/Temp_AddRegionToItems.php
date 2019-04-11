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
        $output->writeln("Getting items");

        $items = $this->em->getRepository(CompanionMarketItemEntry::class)->findAll();
        $total = count($items);

        $output->writeln("Total Items: {$total}");
        $section = (new ConsoleOutput())->section();

        /** @var CompanionMarketItemEntry $item */
        foreach ($items as $item) {
            // get region
            $dc = GameServers::getDataCenter(GameServers::LIST[$item->getServer()]);
            $region = GameServers::LIST_DC_REGIONS[$dc];

            $item->setRegion($region);

            $this->em->persist($item);
            $this->em->flush();

            $section->overwrite("{$item->getId()} {$item->getServer()} = {$item->getRegion()}");
        }
    }
}
