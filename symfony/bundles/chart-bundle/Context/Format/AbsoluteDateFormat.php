<?php

namespace Bundles\ChartBundle\Context\Format;

use Bundles\ChartBundle\Context\Type\AbsoluteDateType;
use Bundles\ChartBundle\Context\Value\AbsoluteDateValue;
use Bundles\ChartBundle\Context\Value\ValueInterface;
use Symfony\Component\Form\FormInterface;

class AbsoluteDateFormat extends AbstractFormat
{
    public function getName() : string
    {
        return 'absolute_date';
    }

    public function getFormType() : string
    {
        return AbsoluteDateType::class;
    }

    public function getValueFromJson(string $jsonValue) : ValueInterface
    {
        return AbsoluteDateValue::createFromJson($jsonValue);
    }

    public function getValueFromForm(FormInterface $form) : ValueInterface
    {
        return new AbsoluteDateValue(
            $form->get('value')->getData()
        );
    }
}