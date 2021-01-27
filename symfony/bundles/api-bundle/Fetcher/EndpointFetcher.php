<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use Bundles\ApiBundle\Model\Facade\SuccessFacade;
use Doctrine\Common\Annotations\AnnotationReader;

class EndpointFetcher
{
    /**
     * @var RolesFetcher
     */
    private $rolesFetcher;

    /**
     * @var FacadeFetcher
     */
    private $facadeFetcher;

    /**
     * @var DocblockFetcher
     */
    private $docblockFetcher;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(RolesFetcher $rolesFetcher,
        FacadeFetcher $facadeFetcher,
        DocblockFetcher $docblockFetcher,
        AnnotationReader $annotationReader)
    {
        $this->rolesFetcher     = $rolesFetcher;
        $this->facadeFetcher    = $facadeFetcher;
        $this->docblockFetcher  = $docblockFetcher;
        $this->annotationReader = $annotationReader;
    }

    public function fetch(ControllerDescription $controller) : EndpointDescription
    {
        $endpoint = new EndpointDescription();

        $endpoint->setPriority($controller->getAnnotation()->priority);

        $reflector   = new \ReflectionMethod($controller->getClass(), $controller->getMethod());
        $annotations = $this->annotationReader->getMethodAnnotations($reflector);
        $docblock    = $this->docblockFetcher->fetch($reflector, $annotations);
        $endpoint->setTitle($docblock->getSummary());
        $endpoint->setDescription($docblock->getDescription());

        $methods = $controller->getRoute()->getMethods() ?: ['GET'];
        $endpoint->setMethods($methods);

        $uri = sprintf('%s%s', getenv('WEBSITE_URL'), $controller->getRoute()->getPath());
        $endpoint->setUri($uri);

        $this->rolesFetcher->fetch($controller, $endpoint);

        if ($controller->getAnnotation()->request) {
            $endpoint->setRequestFacade(
                $this->facadeFetcher->fetch(
                    $controller->getAnnotation()->request->class,
                    $controller->getAnnotation()->request->decorates
                )
            );
        }

        if ($controller->getAnnotation()->response) {
            $endpoint->setResponseFacade(
                $this->facadeFetcher->fetch(
                    SuccessFacade::class,
                    $controller->getAnnotation()->response
                )
            );
        }

        /*
        ✅ private $title;
        ✅ private $method;
        ✅ private $uri;
        ✅ private $roles = [];
        ✅ private $description;
        ✅ private $requestFacade;
        ✅ private $responseFacade;
        private $errors = [];
         */

        return $endpoint;
    }
}