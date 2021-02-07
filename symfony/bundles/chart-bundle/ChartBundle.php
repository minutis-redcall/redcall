<?php

namespace Bundles\ChartBundle;

use Bundles\ChartBundle\DependencyInjection\Compiler\FormatCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ChartBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormatCompilerPass());
    }
}