<?php

namespace App\Form\Type;

use App\Entity\PrefilledAnswers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrefilledAnswersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('label', TextType::class, [
                'label'    => 'prefilled_answers.editor.label',
                'required' => true,
            ])
            ->add('colors', TypesType::class, [
                'label'    => 'prefilled_answers.editor.colors',
                'multiple' => true,
            ])
            ->add('answers', CollectionType::class, [
                'label'         => 'prefilled_answers.editor.answers',
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
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PrefilledAnswers::class,
        ]);
    }
}

