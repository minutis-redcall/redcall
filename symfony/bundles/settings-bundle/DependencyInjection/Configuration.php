<?php

namespace Bundles\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('settings');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
