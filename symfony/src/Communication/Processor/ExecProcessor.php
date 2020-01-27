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
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s send:communication %d', escapeshellarg($console), $communication->getId());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));
    }
}
