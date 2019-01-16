<?php

namespace App\Communication\Processor;

use App\Entity\Communication;
use Symfony\Component\HttpKernel\KernelInterface;

class ExecProcessor implements ProcessorInterface
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function process(Communication $communication)
    {
        if (!function_exists('pcntl_fork')) {
            $this->processCommunication($communication);

            return;
        }

        switch (pcntl_fork()) {
            case -1:
                throw new \RuntimeException(sprintf('Could not fork() in order to process communication %d', $communication->getId()));
                break;
            case 0:
                if (function_exists('posix_setsid')) {
                    posix_setsid();
                }
                $this->processCommunication($communication);
                break;
            default:
                $status = null;
                pcntl_wait($status);
        }
    }

    public function processCommunication(Communication $communication)
    {
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s send:communication %d', escapeshellarg($console), $communication->getId());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));
    }
}
