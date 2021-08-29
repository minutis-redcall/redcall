<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
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

        $property->setName(
            $this->toSnakeCase($propertyName)
        );

        $types = $this->extractor->getTypes($class, $propertyName);
        foreach ($types ?? [] as $type) {
            $type = new TypeDescription($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), $type->isCollection(), $type->getCollectionKeyType(), $type->getCollectionValueType());
            $property->addType($type);
        }

        $reflector   = $this->getProperty($class, $propertyName);
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

            if ($annotation instanceof SerializedName) {
                $property->setName($annotation->getSerializedName());
            }
        }

        return $property;
    }

    public function createCollection(string $propertyName) : PropertyDescription
    {
        $property = new PropertyDescription();
        $property->setName($propertyName);

        return $property;
    }

    private function getProperty(string $class, string $propertyName) : \ReflectionProperty
    {
        // https://stackoverflow.com/a/30878285/731138
        $properties = [];
        try {
            $rc = new \ReflectionClass($class);
            do {
                $rp = [];
                /* @var $p \ReflectionProperty */
                foreach ($rc->getProperties() as $p) {
                    $p->setAccessible(true);
                    $rp[$p->getName()] = $p;
                }
                $properties = array_merge($rp, $properties);
            } while ($rc = $rc->getParentClass());
        } catch (\ReflectionException $e) {
        }

        if (!array_key_exists($propertyName, $properties)) {
            throw new \ReflectionException(sprintf('Property %s::$%s does not exist (check the name of your getters/setters)', $class, $propertyName));
        }

        return $properties[$propertyName];
    }

    private function toSnakeCase(string $propertyName) : string
    {
        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($propertyName)));
    }
}