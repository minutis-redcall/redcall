<?php

use Craue\FormFlowBundle\Twig\Extension\FormFlowExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('twig.extension.craue_formflow', FormFlowExtension::class)
            ->tag('twig.extension')
            ->call('setFormFlowUtil', [new Reference('craue_formflow_util')]);
};
