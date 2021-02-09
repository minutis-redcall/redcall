<?php

namespace Bundles\ChartBundle\Context\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AbsoluteDateType extends AbstractType
{
    public function getParent()
    {
        return DateType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'       => 'chart.context.labels.absolute_date',
            'widget'      => 'single_text',
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }
}