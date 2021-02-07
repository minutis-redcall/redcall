<?php

namespace Bundles\ChartBundle\Context\Format;

use Bundles\ChartBundle\Context\Type\RelativeDateType;
use Bundles\ChartBundle\Context\Value\RelativeDateValue;
use Bundles\ChartBundle\Context\Value\ValueInterface;
use Symfony\Component\Form\FormInterface;

class RelativeDateFormat extends AbstractFormat
{
    public function getName() : string
    {
        return 'relative_date';
    }

    public function getFormType() : string
    {
        return RelativeDateType::class;
    }

    public function getValueFromJson(string $jsonValue) : ValueInterface
    {
        return RelativeDateValue::createFromJson($jsonValue);
    }

    public function getValueFromForm(FormInterface $form) : ValueInterface
    {
        return new RelativeDateValue(
            $form->get('amount')->getData(),
            $form->get('unit')->getData()
        );
    }
}