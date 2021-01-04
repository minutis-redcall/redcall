<?php

namespace Bundles\ChartBundle\ContextType;

use Bundles\ChartBundle\ContextValue\ContextValueInterface;
use Bundles\ChartBundle\ContextValue\StringContextValue;
use Bundles\ChartBundle\Form\Type\Context\StringType;
use Symfony\Component\Form\FormInterface;

class StringContextType extends AbstractContextType
{
    public function getName() : string
    {
        return 'string';
    }

    public function getFormType() : string
    {
        return StringType::class;
    }

    public function getValueFromJson(string $jsonValue) : ContextValueInterface
    {
        return StringContextValue::createFromJson($jsonValue);
    }

    public function getValueFromForm(FormInterface $form) : ContextValueInterface
    {
        return new StringContextValue(
            $form->get('value')->getData()
        );
    }
}