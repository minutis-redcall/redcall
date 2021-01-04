<?php

namespace Bundles\ChartBundle\ContextType;

use Symfony\Component\Form\FormFactoryInterface;

abstract class AbstractContextType implements ContextTypeInterface
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