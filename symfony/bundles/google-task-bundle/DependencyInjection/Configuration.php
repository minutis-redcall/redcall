<?php

namespace Bundles\GoogleTaskBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('google_task');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
