<?php

namespace App\Form\Type;

use App\Entity\Choice;
use App\Form\Model\SmsTrigger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmsTriggerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('audience', AudienceType::class)
            ->add('message', TextareaType::class, [
                'label'    => 'form.communication.fields.body',
                'required' => false,
            ])
            ->add('answers', CollectionType::class, [
                'label'         => 'form.communication.fields.answers',
                'entry_type'    => AnswerType::class,
                'entry_options' => [
                    'label' => false,
                    'attr'  => [
                        'maxlength' => Choice::MAX_LENGTH_SMS,
                    ],
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'prototype'     => true,
                'required'      => false,
                'attr'          => [
                    'class' => 'collection',
                ],
            ])
            ->add('multipleAnswer', CheckboxType::class, [
                'label'    => 'form.communication.fields.multiple_answer',
                'required' => false,
            ])
            ->add('geoLocation', CheckboxType::class, [
                'label'    => 'form.communication.fields.geo_location',
                'required' => false,
            ])
            ->add('test', SubmitType::class, [
                'label' => 'form.communication.fields.test',
                'attr'  => [
                    'class'   => 'trigger-test btn-secondary',
                    'onclick' => "$('form').attr('target', '_blank');",
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.communication.fields.submit',
                'attr'  => [
                    'class'   => 'btn-primary',
                    'onclick' => "$('form').attr('target', '');",
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmsTrigger::class,
            'submit'     => true,
            'attr'       => [
                'class' => 'trigger',
            ],
        ]);
    }
}