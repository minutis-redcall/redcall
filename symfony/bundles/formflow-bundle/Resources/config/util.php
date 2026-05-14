<?php

use Craue\FormFlowBundle\Util\FormFlowUtil;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('craue_formflow_util', FormFlowUtil::class)->public();
    $services->alias(FormFlowUtil::class, 'craue_formflow_util');
};
