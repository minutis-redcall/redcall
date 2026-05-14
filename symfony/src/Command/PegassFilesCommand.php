<?php

namespace App\Command;

use App\Task\PegassCreateChunks;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'pegass:files', description: 'Update pegass database based on files')]
class PegassFilesCommand extends Command
{

    /**
     * @var TaskSender;
     */
    private $taskSender;

    public function __construct(TaskSender $taskSender)
    {
        parent::__construct();

        $this->taskSender = $taskSender;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->taskSender->fire(PegassCreateChunks::class);

        return Command::SUCCESS;
    }
}
