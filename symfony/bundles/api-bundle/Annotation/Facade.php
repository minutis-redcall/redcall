<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * @Annotation
 */
class Facade
{
    /**
     * @Required
     *
     * @var string
     */
    public $class;

    /**
     * @var \Bundles\ApiBundle\Annotation\Facade
     */
    public $decorator;
}