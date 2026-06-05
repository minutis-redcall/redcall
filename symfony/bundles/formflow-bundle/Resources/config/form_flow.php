<?php

use Craue\FormFlowBundle\EventListener\FlowExpiredEventListener;
use Craue\FormFlowBundle\EventListener\PreviousStepInvalidEventListener;
use Craue\FormFlowBundle\Form\Extension\FormFlowFormExtension;
use Craue\FormFlowBundle\Form\Extension\FormFlowHiddenFieldExtension;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowEvents;
use Craue\FormFlowBundle\Storage\DataManager;
use Craue\FormFlowBundle\Storage\SessionStorage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('craue.form.flow.storage_default', SessionStorage::class)
        ->args([new Reference('request_stack')]);

    $services->alias('craue.form.flow.storage', 'craue.form.flow.storage_default')->public();

    $services->set('craue.form.flow.data_manager_default', DataManager::class)
        ->args([new Reference('craue.form.flow.storage')]);

    $services->alias('craue.form.flow.data_manager', 'craue.form.flow.data_manager_default');

    $services->set('craue.form.flow', FormFlow::class)
        ->call('setDataManager', [new Reference('craue.form.flow.data_manager')])
        ->call('setFormFactory', [new Reference('form.factory')])
        ->call('setRequestStack', [new Reference('request_stack')])
        ->call('setEventDispatcher', [new Reference('event_dispatcher', \Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);

    $services->set('craue.form.flow.form_extension', FormFlowFormExtension::class)
        ->tag('form.type_extension', ['extended-type' => FormType::class]);

    $services->set('craue.form.flow.hidden_field_extension', FormFlowHiddenFieldExtension::class)
        ->tag('form.type_extension', ['extended-type' => HiddenType::class]);

    $services->set('craue.form.flow.event_listener.previous_step_invalid', PreviousStepInvalidEventListener::class)
        ->tag('kernel.event_listener', [
            'event'  => FormFlowEvents::PREVIOUS_STEP_INVALID,
            'method' => 'onPreviousStepInvalid',
        ])
        ->call('setTranslator', [new Reference('translator')]);

    $services->set('craue.form.flow.event_listener.flow_expired', FlowExpiredEventListener::class)
        ->tag('kernel.event_listener', [
            'event'  => FormFlowEvents::FLOW_EXPIRED,
            'method' => 'onFlowExpired',
        ])
        ->call('setTranslator', [new Reference('translator')]);
};
