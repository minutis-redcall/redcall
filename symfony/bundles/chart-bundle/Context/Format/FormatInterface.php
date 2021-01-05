<?php

namespace Bundles\ChartBundle\Context\Format;

use Bundles\ChartBundle\Context\Value\ValueInterface;
use Symfony\Component\Form\FormInterface;

interface FormatInterface
{
    public function getName() : string;

    public function getFormType() : string;

    public function getTranslationKey() : string;

    public function getValueFromJson(string $jsonValue) : ValueInterface;

    public function getValueFromForm(FormInterface $form) : ValueInterface;
}