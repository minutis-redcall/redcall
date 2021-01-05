<?php

namespace Bundles\ChartBundle\Context\Format;

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
    }

    public function getValueFromJson(string $jsonValue) : ValueInterface
    {
        // TODO: Implement getValueFromJson() method.
    }

    public function getValueFromForm(FormInterface $form) : ValueInterface
    {
        // TODO: Implement getValueFromForm() method.
    }

}