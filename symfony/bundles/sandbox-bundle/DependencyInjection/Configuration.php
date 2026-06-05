<?php

namespace Bundles\SandboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : \Symfony\Component\Config\Definition\Builder\TreeBuilder
    {
        $rootNode = new TreeBuilder('sandbox');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
