<?php

namespace Bundles\ChartBundle\Context\Type;

use Bundles\ChartBundle\Context\Value\RelativeDateValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class RelativeDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $units = array_flip(array_map(function ($unit) {
            return sprintf('chart.context.relative_date.unit_%s', $unit);
        }, RelativeDateValue::UNITS));

        $builder
            ->add('amount', NumberType::class, [
                'label'       => 'chart.context.labels.relative_date.amount',
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 0]),
                ],
            ])
            ->add('unit', ChoiceType::class, [
                'label'       => 'chart.context.labels.relative_date.unit',
                'choices'     => $units,
                'constraints' => [
                    new NotBlank(),
                    new Choice(['choices' => $units]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('label', false);
    }
}