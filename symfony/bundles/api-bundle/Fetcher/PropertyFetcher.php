<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Validator\Constraint;

class PropertyFetcher
{
    /**
     * @var DocblockFetcher
     */
    private $docblockFetcher;

    /**
     * @var ConstraintFetcher
     */
    private $constraintFetcher;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(DocblockFetcher $docblockFetcher,
        ConstraintFetcher $constraintFetcher,
        PropertyInfoExtractorInterface $extractor,
        AnnotationReader $annotationReader)
    {
        $this->docblockFetcher   = $docblockFetcher;
        $this->constraintFetcher = $constraintFetcher;
        $this->extractor         = $extractor;
        $this->annotationReader  = $annotationReader;
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
        if ('type' === $propertyName) {
            dd($docblock);
        }

        if ($parent) {
            $property->setParent($parent);
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Constraint) {
                $property->addConstraint(
                    $this->constraintFetcher->fetch($annotation)
                );
            }
        }

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