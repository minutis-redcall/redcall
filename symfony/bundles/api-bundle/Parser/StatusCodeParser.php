<?php

namespace Bundles\ApiBundle\Parser;

use Bundles\ApiBundle\Model\Facade\FacadeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Response;

class StatusCodeParser
{
    /**
     * @var AnnotationReader|null
     */
    private $annotationReader;

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
            $classAnnotations = $this->getAnnotationReader()->getClassAnnotations($reflector);

            foreach ($classAnnotations as $annot) {
                if ($annot instanceof \Bundles\ApiBundle\Annotation\StatusCode) {
                    return $annot->value;
                }
            }
        } while ($reflector = $reflector->getParentClass());

        return null;
    }

    private function getAnnotationReader() : AnnotationReader
    {
        if ($this->annotationReader) {
            return $this->annotationReader;
        }

        $this->annotationReader = new AnnotationReader();

        return $this->annotationReader;
    }
}