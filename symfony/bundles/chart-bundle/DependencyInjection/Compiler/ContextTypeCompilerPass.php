<?php

namespace Bundles\ChartBundle\DependencyInjection\Compiler;

use Bundles\ChartBundle\Bag\ContextTypeBag;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContextTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ContextTypeBag::class)) {
            return;
        }

        $definition = $container->findDefinition(ContextTypeBag::class);

        $taggedServices = $container->findTaggedServiceIds('chart.context_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addContextType', [new Reference($id)]);
        }
    }
}