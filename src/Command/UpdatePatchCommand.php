<?php

namespace App\Command;

use App\Service\GamePatch\Patch;
use App\Service\GamePatch\PatchContent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePatchCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('UpdatePatchCommand')
            ->setDescription('Update game patch values')
            ->addArgument('force', InputArgument::OPTIONAL, 'Force this instance')
            ->addArgument('single', InputArgument::OPTIONAL, 'Specified content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title('Update patch values');
        
        $patch = (new Patch())->getLatest();

        if ($input->getArgument('force') || $this->io->confirm('Is the current patch: '. $patch->Name_en, false)) {
            (new PatchContent())->init($this->io)->handle($input->getArgument('single'));
        }

    }
}
