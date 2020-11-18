<?php

namespace Bundles\GoogleTaskBundle\DependencyInjection\Compiler;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaskCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(TaskBag::class)) {
            return;
        }

        $definition = $container->findDefinition(TaskBag::class);

        $taggedServices = $container->findTaggedServiceIds('google_task');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTask', [new Reference($id)]);
        }
    }
}