<?php

namespace App\Form\Type;

use App\Form\Model\BaseTrigger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class ChooseOperationChoicesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Dynamically creating the "operation choices" field based on given answers
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var BaseTrigger $trigger */
            $trigger = $event->getData();

            $answers = $trigger->getAnswers();
            $choices = array_combine($answers, $answers);

            $event
                ->getForm()
                ->add('operationAnswers', ChoiceType::class, [
                    'label'       => 'form.operation.fields.choices.list',
                    'choices'     => $choices,
                    'constraints' => [
                        new Choice([
                            'choices' => $choices,
                            'multiple' => true,
                        ]),
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                ])
                ->add('submit', SubmitType::class, [
                    'label' => 'form.operation.buttons.submit',
                    'attr'  => [
                        'class' => 'btn btn-primary trigger-launch',
                    ],
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BaseTrigger::class,
        ]);
    }
}