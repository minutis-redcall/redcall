<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointCollectionDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class EndpointCollectionFetcher
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
     * @var EndpointFetcher
     */
    private $endpointFetcher;

    public function __construct(RouterInterface $router,
        ControllerResolverInterface $resolver,
        ?AnnotationReader $annotationReader,
        EndpointFetcher $endpointFetcher)
    {
        $this->router           = $router;
        $this->resolver         = $resolver;
        $this->annotationReader = $annotationReader;
        $this->endpointFetcher  = $endpointFetcher;
    }

    public function fetch() : EndpointCollectionDescription
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
                    $endpoints->add($this->endpointFetcher->fetch(
                        new ControllerDescription($route, get_class($service), $method, $annotation)
                    ));
                }
            }
        }

        return $endpoints;
    }
}