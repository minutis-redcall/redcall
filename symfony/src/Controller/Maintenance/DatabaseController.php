<?php

namespace App\Controller\Maintenance;

use Nexmo\Application\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @Route(path="migrate")
 */
class MigrateController
{
    /**
     * @Route(path="/run")
     */
    public function run(Request $request, KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command'          => 'doctrine:migration:migrate',
            '--no-interaction' => true,
        ]);

        $application->run($input, new NullOutput());
    }
}