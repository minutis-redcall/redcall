<?php

namespace Bundles\PegassCrawlerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('pegass_crawler');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
