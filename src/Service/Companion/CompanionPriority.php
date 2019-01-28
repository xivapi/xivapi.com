<?php

namespace App\Service\Companion;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class CompanionPriority
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->console = new ConsoleOutput();
    }

    public function calculate()
    {
        $this->console->

    }
}
