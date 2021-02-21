<?php

namespace Bundles\ApiBundle\Model\Facade\Http;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Model\Facade\EmptyFacade;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Api\StatusCode(Response::HTTP_UNSUPPORTED_MEDIA_TYPE)
 */
class HttpUnsupportedMediaTypeFacade extends EmptyFacade
{
}