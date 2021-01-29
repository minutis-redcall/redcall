<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
final class Placeholder
{
    /**
     * @var string
     * @Required
     */
    public $toReplace;

    /**
     * @Required
     */
    public $replaceBy;
}