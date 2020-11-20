<?php

namespace Bundles\GoogleTaskBundle;

use Bundles\GoogleTaskBundle\DependencyInjection\Compiler\TaskCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GoogleTaskBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TaskCompilerPass());
    }
}
