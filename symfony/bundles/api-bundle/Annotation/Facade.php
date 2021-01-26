<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * @Annotation
 * @Target({"ANNOTATION"})
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