<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use Bundles\ApiBundle\Model\Documentation\RoleDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessMapInterface;

class RolesFetcher
{
    /**
     * @var AccessMapInterface
     */
    private $accessMap;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(AccessMapInterface $accessMap, AnnotationReader $annotationReader)
    {
        $this->accessMap        = $accessMap;
        $this->annotationReader = $annotationReader;
    }

    public function fetch(ControllerDescription $controller, EndpointDescription $endpoint)
    {
        $this->fetchFromAccessMap($endpoint);
        $this->fetchFromAnnotations($controller, $endpoint);
    }

    /**
     * Fetch roles located in access_control block of security.yaml
     *
     * @param ControllerDescription $controller
     * @param EndpointDescription   $endpoint
     */
    private function fetchFromAccessMap(EndpointDescription $endpoint)
    {
        foreach ($endpoint->getMethods() as $method) {
            $request = Request::create($endpoint->getUri(), $method);
            [$attributes, $channel] = $this->accessMap->getPatterns($request);

            foreach ($attributes ?? [] as $attribute) {
                $role = new RoleDescription();
                $role->setMethod(count($endpoint->getMethods()) ? $method : null);
                $role->setAttribute($attribute);
                $role->setChannel($channel);

                $endpoint->addRole($role);
            }
        }
    }

    /**
     * Fetch roles based on IsGranted and Security annotations
     *
     * @param ControllerDescription $controller
     * @param EndpointDescription   $endpoint
     */
    private function fetchFromAnnotations(ControllerDescription $controller, EndpointDescription $endpoint)
    {
        $class            = new \ReflectionClass($controller->getClass());
        $classAnnotations = $this->annotationReader->getClassAnnotations($class);

        $method            = new \ReflectionMethod($controller->getClass(), $controller->getMethod());
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($method);

        foreach (array_merge($classAnnotations, $methodAnnotations) as $annotation) {
            if ($annotation instanceof IsGranted) {
                $this->fetchFromIsGrantedAnnotation($annotation, $endpoint);
            }

            if ($annotation instanceof Security) {
                $this->fetchFromSecurityAnnotation($annotation, $endpoint);
            }
        }
    }

    private function fetchFromIsGrantedAnnotation(IsGranted $annotation, EndpointDescription $endpoint)
    {
        $attributes = $annotation->getAttributes();

        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attribute) {
            $role = new RoleDescription();
            $role->setAttribute($attribute);
            $role->setSubject($annotation->getSubject());

            $endpoint->addRole($role);
        }
    }

    private function fetchFromSecurityAnnotation(Security $annotation, EndpointDescription $endpoint)
    {
        $role = new RoleDescription();
        $role->setAttribute($annotation->getExpression());

        $endpoint->addRole($role);
    }
}