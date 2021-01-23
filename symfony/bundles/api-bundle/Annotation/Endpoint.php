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
     * @var \Bundles\ApiBundle\Annotation\Facade
     */
    public $request;

    /**
     * @var \Bundles\ApiBundle\Annotation\Facade
     */
    public $response;
}