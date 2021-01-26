<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Annotation\Endpoint;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class EndpointListFetcher
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

    public function __construct(RouterInterface $router,
        ControllerResolverInterface $resolver,
        ?AnnotationReader $annotationReader)
    {
        $this->router           = $router;
        $this->resolver         = $resolver;
        $this->annotationReader = $annotationReader;
    }

    public function getApiEndpoints() : array
    {
        $endpoints = [];

        foreach ($this->router->getRouteCollection() as $route) {
            $request = new Request();
            $request->attributes->add($route->getDefaults());

            [$service, $method] = $this->resolver->getController($request);

            $reflector   = new \ReflectionMethod($service, $method);
            $annotations = $this->annotationReader->getMethodAnnotations($reflector);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Endpoint) {
                    $endpoints[] = [
                        'class'  => get_class($service),
                        'method' => $method,
                    ];
                }
            }
        }

        return $endpoints;
    }
}