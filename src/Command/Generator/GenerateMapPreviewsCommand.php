<?php

namespace App\Command\Generator;

use App\Command\CommandHelperTrait;
use App\Service\Maps\PreviewGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMapPreviewsCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var PreviewGenerator */
    private $generator;
    
    public function __construct(PreviewGenerator $generator, ?string $name = null)
    {
        parent::__construct($name);

        $this->generator = $generator;
    }
    
    protected function configure()
    {
        $this
            ->setName('GenerateMapPreviewsCommand')
            ->setDescription('Generate map previews of positions')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generator->setSymfonyStyle($input, $output);
        $this->generator->generate();
    }
}
