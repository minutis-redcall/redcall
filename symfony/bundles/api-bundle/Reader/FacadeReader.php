<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Documentation\FacadeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\SerializerInterface;

class FacadeReader
{
    /**
     * @var DocblockReader
     */
    private $docblockReader;

    /**
     * @var StatusCodeReader
     */
    private $statusCodeReader;

    /**
     * @var PropertyCollectionReader
     */
    private $propertyCollectionReader;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(DocblockReader $docblockReader,
        StatusCodeReader $statusCodeReader,
        PropertyCollectionReader $propertyCollectionReader,
        AnnotationReader $annotationReader,
        SerializerInterface $serializer)
    {
        $this->docblockReader           = $docblockReader;
        $this->statusCodeReader         = $statusCodeReader;
        $this->propertyCollectionReader = $propertyCollectionReader;
        $this->annotationReader         = $annotationReader;
        $this->serializer               = $serializer;
    }

    public function read(string $class, ?Facade $decorates) : FacadeDescription
    {
        $facade = new FacadeDescription();

        $reflector   = new \ReflectionClass($class);
        $annotations = $this->annotationReader->getClassAnnotations($reflector);
        $docblock    = $this->docblockReader->read($reflector, $annotations);
        $facade->setTitle($docblock->getSummary());
        $facade->setDescription($docblock->getDescription());

        $example = $class::getExample($decorates);

        $serialized = $this->serializer->serialize($example, 'json');
        $facade->setExample(json_decode($serialized, true));

        $properties = $this->propertyCollectionReader->read($example);
        $facade->setProperties($properties);

        $statusCode = $this->statusCodeReader->getStatusCode($example);
        $facade->setStatusCode($statusCode);

        return $facade;
    }
}
