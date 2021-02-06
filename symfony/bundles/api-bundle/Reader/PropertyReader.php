<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Validator\Constraint;

class PropertyReader
{
    /**
     * @var DocblockReader
     */
    private $docblockReader;

    /**
     * @var ConstraintReader
     */
    private $constraintReader;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(DocblockReader $docblockReader,
        ConstraintReader $constraintReader,
        PropertyInfoExtractorInterface $extractor,
        AnnotationReader $annotationReader)
    {
        $this->docblockReader   = $docblockReader;
        $this->constraintReader = $constraintReader;
        $this->extractor        = $extractor;
        $this->annotationReader = $annotationReader;
    }

    public function read(string $class, string $propertyName, ?PropertyDescription $parent) : PropertyDescription
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
        $docblock    = $this->docblockReader->read($reflector, $annotations);

        $property->setTitle($docblock->getSummary());
        $property->setDescription($docblock->getDescription());

        if ($parent) {
            $property->setParent($parent);
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Constraint) {
                $property->addConstraint(
                    $this->constraintReader->read($annotation)
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