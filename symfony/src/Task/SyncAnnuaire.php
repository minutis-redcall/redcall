<?php

namespace App\Task;

use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class SyncAnnuaire implements TaskInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function execute(array $context)
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => 'annuaire']);

        $application->run($input, new NullOutput());
    }

    public function getQueueName() : string
    {
        return Queues::PEGASS_CREATE_CHUNKS;
    }
}