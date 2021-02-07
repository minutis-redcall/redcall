<?php

namespace Bundles\ChartBundle\Context\Format;

use Bundles\ChartBundle\Context\Type\StringType;
use Bundles\ChartBundle\Context\Value\StringValue;
use Bundles\ChartBundle\Context\Value\ValueInterface;
use Symfony\Component\Form\FormInterface;

class StringFormat extends AbstractFormat
{
    public function getName() : string
    {
        return 'string';
    }

    public function getFormType() : string
    {
        return StringType::class;
    }

    public function getValueFromJson(string $jsonValue) : ValueInterface
    {
        return StringValue::createFromJson($jsonValue);
    }

    public function getValueFromForm(FormInterface $form) : ValueInterface
    {
        return new StringValue(
            $form->get('value')->getData()
        );
    }
}