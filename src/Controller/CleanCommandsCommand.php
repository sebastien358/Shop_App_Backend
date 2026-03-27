<?php

namespace App\Controller;

use App\Services\CommandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clean-commands',
    description: 'Supprime les commandes en attente expirées'
)]
class CleanCommandsCommand extends Command
{
    protected static $defaultName = 'app:clean-commands';
    private CommandService $commandService;

    public function __construct(CommandService $commandService)
    {
        $this->commandService = $commandService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandService->handleCommands();
        $output->writeln('Commandes expirées supprimées ✅');

        return Command::SUCCESS;
    }
}
