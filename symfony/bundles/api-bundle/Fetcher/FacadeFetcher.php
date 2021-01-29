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
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(DocblockFetcher $docblockFetcher,
        StatusCodeFetcher $statusCodeFetcher,
        AnnotationReader $annotationReader,
        SerializerInterface $serializer)
    {
        $this->docblockFetcher   = $docblockFetcher;
        $this->statusCodeFetcher = $statusCodeFetcher;
        $this->annotationReader  = $annotationReader;
        $this->serializer        = $serializer;
    }

    public function fetch(string $class, ?Facade $decorates) : FacadeDescription
    {
        $facade = new FacadeDescription();

        $reflector   = new \ReflectionClass($class);
        $annotations = $this->annotationReader->getClassAnnotations($reflector);
        $docblock    = $this->docblockFetcher->fetch($reflector, $annotations);
        $facade->setTitle($docblock->getSummary());
        $facade->setDescription($docblock->getDescription());

        // todo properties

        $example = $class::getExample($decorates);

        $serialized = $this->serializer->serialize($example, 'json');
        $facade->setExample($serialized);

        $statusCode = $this->statusCodeFetcher->getStatusCode($example);
        $facade->setStatusCode($statusCode);

        /*
                facade:
                ✅ private $title;
                ✅ private $description;
                private $properties = [];
                ✅ private $example;
                ✅ private $statusCode;

                properties:
                private $name;
                private $type;
                private $description;
                private $constraints = [];

                constrants:
                private $name;
                private $options = [];
        */

        return $facade;
    }
}
