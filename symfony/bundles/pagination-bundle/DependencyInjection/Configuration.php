<?php

namespace Bundles\PaginationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('pagination');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
