<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Documentation\FacadeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\SerializerInterface;

class FacadeFetcher
{
    /**
     * @var DocblockFetcher
     */
    private $docblockFetcher;

    /**
     * @var StatusCodeFetcher
     */
    private $statusCodeFetcher;

    /**
     * @var PropertyCollectionFetcher
     */
    private $propertyCollectionFetcher;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(DocblockFetcher $docblockFetcher,
        StatusCodeFetcher $statusCodeFetcher,
        PropertyCollectionFetcher $propertyCollectionFetcher,
        AnnotationReader $annotationReader,
        SerializerInterface $serializer)
    {
        $this->docblockFetcher           = $docblockFetcher;
        $this->statusCodeFetcher         = $statusCodeFetcher;
        $this->propertyCollectionFetcher = $propertyCollectionFetcher;
        $this->annotationReader          = $annotationReader;
        $this->serializer                = $serializer;
    }

    public function fetch(string $class, ?Facade $decorates) : FacadeDescription
    {
        $facade = new FacadeDescription();

        $reflector   = new \ReflectionClass($class);
        $annotations = $this->annotationReader->getClassAnnotations($reflector);
        $docblock    = $this->docblockFetcher->fetch($reflector, $annotations);
        $facade->setTitle($docblock->getSummary());
        $facade->setDescription($docblock->getDescription());

        $example = $class::getExample($decorates);

        $serialized = $this->serializer->serialize($example, 'json');
        $facade->setExample($serialized);

        $properties = $this->propertyCollectionFetcher->fetch($example);
        $facade->setProperties($properties);

        $statusCode = $this->statusCodeFetcher->getStatusCode($example);
        $facade->setStatusCode($statusCode);

        return $facade;
    }
}
