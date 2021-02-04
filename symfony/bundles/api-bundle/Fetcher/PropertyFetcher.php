<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class PropertyFetcher
{
    /**
     * @var DocblockFetcher
     */
    private $docblockFetcher;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(DocblockFetcher $docblockFetcher,
        PropertyInfoExtractorInterface $extractor,
        AnnotationReader $annotationReader)
    {
        $this->docblockFetcher  = $docblockFetcher;
        $this->extractor        = $extractor;
        $this->annotationReader = $annotationReader;
    }

    public function fetch(string $class, string $propertyName, ?PropertyDescription $parent) : PropertyDescription
    {
        $property = new PropertyDescription();
        $property->setName($propertyName);

        $types = $this->extractor->getTypes($class, $propertyName);
        foreach ($types ?? [] as $type) {
            $type = new TypeDescription($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), $type->isCollection(), $type->getCollectionKeyType(), $type->getCollectionValueType());
            $property->addType($type);
        }

        $reflector   = new \ReflectionProperty($class, $propertyName);
        $annotations = $this->annotationReader->getPropertyAnnotations($reflector);
        $docblock    = $this->docblockFetcher->fetch($reflector, $annotations);

        $property->setTitle($docblock->getSummary());
        $property->setDescription($docblock->getDescription());

        if ($parent) {
            $property->setParent($parent);
        }

        // TODO constraints

        return $property;
    }

    public function createCollection(string $propertyName) : PropertyDescription
    {
        $property = new PropertyDescription();
        $property->setName($propertyName);
        $property->setCollection(true);

        return $property;
    }
}