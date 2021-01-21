<?php

namespace Bundles\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class ApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('controller.yaml');
        $loader->load('manager.yaml');
        $loader->load('listener.yaml');
        $loader->load('parser.yaml');
        $loader->load('repository.yaml');
        $loader->load('security.yaml');
        $loader->load('twig.yaml');
    }
}