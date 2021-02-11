<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Model\Documentation\CategoryCollectionDescription;
use Bundles\ApiBundle\Model\Documentation\CategoryDescription;
use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

class CategoryCollectionReader
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

    /**
     * @var DocblockReader
     */
    private $docblockReader;

    public function __construct(RouterInterface $router,
        ControllerResolverInterface $resolver,
        ?AnnotationReader $annotationReader,
        EndpointReader $endpointReader,
        DocblockReader $docblockReader)
    {
        $this->router           = $router;
        $this->resolver         = $resolver;
        $this->annotationReader = $annotationReader;
        $this->endpointReader   = $endpointReader;
        $this->docblockReader   = $docblockReader;
    }

    public function read() : CategoryCollectionDescription
    {
        $categoryCollection = new CategoryCollectionDescription();
        $categories         = [];

        foreach ($this->router->getRouteCollection() as $route) {
            $request = new Request();
            $request->attributes->add($route->getDefaults());

            [$service, $method] = $this->resolver->getController($request);

            $class        = get_class($service);
            $categoryName = substr(substr($class, strrpos($class, '\\') + 1), 0, -10);

            $reflector   = new \ReflectionMethod($service, $method);
            $annotations = $this->annotationReader->getMethodAnnotations($reflector);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Endpoint) {
                    if (!array_key_exists($categoryName, $categories)) {
                        $categoryCollection->add(
                            $categories[$categoryName] = $this->createCategory($categoryName, $class)
                        );
                    }

                    $categories[$categoryName]->getEndpoints()->add($this->endpointReader->read(
                        new ControllerDescription($route, get_class($service), $method, $annotation)
                    ));
                }
            }
        }

        $categoryCollection->sort();

        return $categoryCollection;
    }

    private function createCategory(string $categoryName, string $class) : CategoryDescription
    {
        $category = new CategoryDescription();
        $category->setName($categoryName);

        $reflector   = new \ReflectionClass($class);
        $annotations = $this->annotationReader->getClassAnnotations($reflector);
        $docblock    = $this->docblockReader->read($reflector, $annotations);

        $category->setTitle($docblock->getSummary());
        $category->setDescription($docblock->getDescription());

        return $category;
    }
}