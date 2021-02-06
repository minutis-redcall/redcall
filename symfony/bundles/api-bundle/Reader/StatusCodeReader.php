<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Annotation\StatusCode;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Response;

class StatusCodeReader
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function getStatusCode(FacadeInterface $facade)
    {
        $className = get_class($facade);
        $reflector = new \ReflectionClass($className);

        if ($code = $this->findStatusCodeInReflection($reflector)) {
            return $code;
        }

        foreach ($reflector->getInterfaces() as $interface) {
            if ($code = $this->findStatusCodeInReflection($interface)) {
                return $code;
            }
        }

        return Response::HTTP_OK;
    }

    private function findStatusCodeInReflection(\ReflectionClass $reflector) : ?int
    {
        do {
            $classAnnotations = $this->annotationReader->getClassAnnotations($reflector);

            foreach ($classAnnotations as $annot) {
                if ($annot instanceof StatusCode) {
                    return $annot->value;
                }
            }
        } while ($reflector = $reflector->getParentClass());

        return null;
    }
}