<?php

namespace Bundles\ApiBundle\Annotation;

/**
 * This annotation helps generating the API documentation.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Endpoint
{
    /**
     * @var int
     */
    public $priority = 1;

    /**
     * @var \Bundles\ApiBundle\Annotation\Facade
     */
    public $request;

    /**
     * @var \Bundles\ApiBundle\Annotation\Facade
     */
    public $response;
}