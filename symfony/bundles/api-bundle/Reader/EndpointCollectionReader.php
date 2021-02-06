<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointCollectionDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class EndpointCollectionReader
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    /**
     * @var AnnotationReader|null
     */
    private $annotationReader;

    /**
     * @var EndpointReader
     */
    private $endpointReader;

    public function __construct(RouterInterface $router,
        ControllerResolverInterface $resolver,
        ?AnnotationReader $annotationReader,
        EndpointReader $endpointReader)
    {
        $this->router           = $router;
        $this->resolver         = $resolver;
        $this->annotationReader = $annotationReader;
        $this->endpointReader   = $endpointReader;
    }

    public function read() : EndpointCollectionDescription
    {
        $endpoints = new EndpointCollectionDescription();
        foreach ($this->router->getRouteCollection() as $route) {

            $request = new Request();
            $request->attributes->add($route->getDefaults());

            [$service, $method] = $this->resolver->getController($request);

            $reflector   = new \ReflectionMethod($service, $method);
            $annotations = $this->annotationReader->getMethodAnnotations($reflector);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Endpoint) {
                    $endpoints->add($this->endpointReader->read(
                        new ControllerDescription($route, get_class($service), $method, $annotation)
                    ));
                }
            }
        }

        $endpoints->sort();

        return $endpoints;
    }
}