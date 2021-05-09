<?php

namespace App\Form\Type;

use App\Form\Model\Operation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Choice;

abstract class BaseCreateOrUseOperationType extends AbstractType
{
    protected function prepareChoices(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Operation $data */
            $answers = $event->getData()->campaign->trigger->getAnswers();

            if ($answers) {
                $event->getForm()->add('choices', ChoiceType::class, [
                    'label'       => 'form.operation.fields.choices.list',
                    'choices'     => array_flip($answers),
                    'constraints' => [
                        new Choice(['choices' => array_flip($answers)]),
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                ]);
            }
        });
    }
}