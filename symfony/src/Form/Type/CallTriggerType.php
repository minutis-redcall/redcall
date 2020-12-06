<?php

namespace App\Form\Type;

use App\Form\Model\CallTrigger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CallTriggerType extends AbstractType
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
            'data_class' => CallTrigger::class,
            'submit'     => true,
        ]);
    }
}