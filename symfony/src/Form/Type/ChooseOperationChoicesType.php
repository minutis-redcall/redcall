<?php

namespace App\Form\Type;

use App\Form\Model\BaseTrigger;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChooseOperationChoicesType
{

    /*
            ->add('submit', SubmitType::class, [

                // define the field in a PRE_SET event depending on the
                //'label' => 'form.operation.fields.use',
                //'label' => 'form.operation.fields.create',


                'label' => 'form.operation.fields.create',
                'attr'  => [
                    'class' => 'btn btn-primary trigger-launch',
                ],
            ])     */

    /*
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
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
     */

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BaseTrigger::class,
        ]);
    }
}