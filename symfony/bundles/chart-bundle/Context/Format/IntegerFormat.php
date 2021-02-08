<?php

namespace Bundles\ChartBundle\Context\Format;

use Bundles\ChartBundle\Context\Type\IntegerType;
use Bundles\ChartBundle\Context\Value\IntegerValue;
use Bundles\ChartBundle\Context\Value\ValueInterface;
use Symfony\Component\Form\FormInterface;

class IntegerFormat extends AbstractFormat
{
    public function getName() : string
    {
        return 'integer';
    }

    public function getFormType() : string
    {
        return IntegerType::class;
    }

    public function getValueFromJson(string $jsonValue) : ValueInterface
    {
        return IntegerValue::createFromJson($jsonValue);
    }

    public function getValueFromForm(FormInterface $form) : ValueInterface
    {
        return new IntegerValue(
            $form->get('value')->getData()
        );
    }
}