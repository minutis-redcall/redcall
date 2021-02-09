<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use Bundles\ApiBundle\Model\Facade\SuccessFacade;
use Doctrine\Common\Annotations\AnnotationReader;

class EndpointReader
{
    /**
     * @var RolesReader
     */
    private $rolesReader;

    /**
     * @var FacadeReader
     */
    private $facadeReader;

    /**
     * @var DocblockReader
     */
    private $docblockReader;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(RolesReader $rolesReader,
        FacadeReader $facadeReader,
        DocblockReader $docblockReader,
        AnnotationReader $annotationReader)
    {
        $this->rolesReader      = $rolesReader;
        $this->facadeReader     = $facadeReader;
        $this->docblockReader   = $docblockReader;
        $this->annotationReader = $annotationReader;
    }

    public function read(ControllerDescription $controller) : EndpointDescription
    {
        $endpoint = new EndpointDescription();

        $category = substr(substr($controller->getClass(), strrpos($controller->getClass(), '\\') + 1), 0, -10);
        $endpoint->setCategory($category);

        $endpoint->setPriority($controller->getAnnotation()->priority);

        $reflector   = new \ReflectionMethod($controller->getClass(), $controller->getMethod());
        $annotations = $this->annotationReader->getMethodAnnotations($reflector);
        $docblock    = $this->docblockReader->read($reflector, $annotations);
        if ($docblock->getSummary()) {
            $endpoint->setTitle($docblock->getSummary());
        } else {
            $endpoint->setTitle($this->camelToSpace($controller->getMethod()));
        }
        $endpoint->setDescription($docblock->getDescription());

        $methods = $controller->getRoute()->getMethods() ?: ['GET'];
        $endpoint->setMethods($methods);

        $uri = sprintf('%s%s', getenv('WEBSITE_URL'), $controller->getRoute()->getPath());
        $endpoint->setUri($uri);

        $this->rolesReader->read($controller, $endpoint);

        if ($controller->getAnnotation()->request) {
            $endpoint->setRequestFacade(
                $this->facadeReader->read(
                    $controller->getAnnotation()->request->class,
                    $controller->getAnnotation()->request->decorates
                )
            );
        }

        if ($controller->getAnnotation()->response) {
            $endpoint->setResponseFacade(
                $this->facadeReader->read(
                    SuccessFacade::class,
                    $controller->getAnnotation()->response
                )
            );
        }

        return $endpoint;
    }

    private function camelToSpace(string $camelValue)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $camelValue));
    }
}