<?php

namespace Bundles\ChartBundle\Context\Format;

use Symfony\Component\Form\FormFactoryInterface;

abstract class AbstractFormat implements FormatInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function getTranslationKey() : string
    {
        return 'chart.context.types.'.$this->getName();
    }
}