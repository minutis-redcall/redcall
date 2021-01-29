<?php

namespace Bundles\PasswordLoginBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('password_login');

        // ¯\_(ツ)_/¯

        return $rootNode;
    }
}
