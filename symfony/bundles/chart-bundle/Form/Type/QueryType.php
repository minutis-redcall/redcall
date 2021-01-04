<?php

namespace Bundles\ChartBundle\Form\Type;

use Bundles\ChartBundle\Entity\StatQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class QueryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => 'chart.query.edit.name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('query', TextareaType::class, [
                'label'       => 'chart.query.edit.query',
                'constraints' => [
                    new NotBlank(),
                    new Callback(function ($subject, ExecutionContextInterface $context) {
                        if (0 !== stripos($subject, 'SELECT')) {
                            $context->addViolation('chart.violations.query.select');
                        }
                    }),
                ],
                'attr'        => [
                    'rows' => 8,
                ],
            ])
            ->add('context', CollectionType::class, [
                'label'         => 'chart.query.edit.context',
                'entry_type'    => ContextType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'prototype'     => true,
                'required'      => false,
                'constraints'   => [
                    new Valid(),
                ],
            ]);

        $builder->get('context')->addModelTransformer(
            new CallbackTransformer(
                function ($fromModel) {
                    return json_encode($fromModel);
                },
                function ($fromForm) {
                    return json_decode($fromForm, true);
                }
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'query';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StatQuery::class,
        ]);
    }
}