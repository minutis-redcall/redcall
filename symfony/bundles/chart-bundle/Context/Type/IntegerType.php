<?php

namespace Bundles\ChartBundle\Context\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class IntegerType extends AbstractType
{
    public function getParent()
    {
        return NumberType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'       => 'chart.context.labels.integer',
            'html5'       => true,
            'constraints' => [
                new NotBlank(),
                new Regex('/^\d+$/'),
            ],
        ]);
    }
}