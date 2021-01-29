<?php

namespace Bundles\ApiBundle\Contracts;

use Bundles\ApiBundle\Annotation as Api;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Api\StatusCode(Response::HTTP_OK)
 */
interface FacadeInterface
{
    static public function getExample(Api\Facade $decorates = null) : FacadeInterface;
}