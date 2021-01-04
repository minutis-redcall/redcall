<?php

namespace Bundles\ChartBundle\ContextType;

use Bundles\ChartBundle\ContextValue\ContextValueInterface;
use Symfony\Component\Form\FormInterface;

interface ContextTypeInterface
{
    public function getName() : string;

    public function getFormType() : string;

    public function getTranslationKey() : string;

    public function getValueFromJson(string $jsonValue) : ContextValueInterface;

    public function getValueFromForm(FormInterface $form) : ContextValueInterface;
}