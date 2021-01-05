<?php

namespace Bundles\ChartBundle\DependencyInjection\Compiler;

use Bundles\ChartBundle\Context\Bag\FormatBag;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormatCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(FormatBag::class)) {
            return;
        }

        $definition = $container->findDefinition(FormatBag::class);

        $taggedServices = $container->findTaggedServiceIds('chart.context_format');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addFormat', [new Reference($id)]);
        }
    }
}