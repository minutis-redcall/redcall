<?php

namespace App\Form\Type;

use App\Form\Model\CampaignOperation;
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
            /** @var CampaignOperation $data */
            $answers = $event->getData()->campaign->trigger->getAnswers();
            $choices = array_combine($answers, $answers);

            if ($answers) {
                $event->getForm()->add('choices', ChoiceType::class, [
                    'label'       => 'form.operation.fields.choices.list',
                    'choices'     => $choices,
                    'constraints' => [
                        new Choice(['choices' => $choices]),
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                ]);
            }
        });
    }
}