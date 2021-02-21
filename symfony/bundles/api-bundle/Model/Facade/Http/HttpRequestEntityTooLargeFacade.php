<?php

namespace Bundles\ApiBundle\Model\Facade\Http;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Model\Facade\EmptyFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Api\StatusCode(Response::HTTP_REQUEST_ENTITY_TOO_LARGE)
 */
class HttpRequestEntityTooLargeFacade extends EmptyFacade
{
}