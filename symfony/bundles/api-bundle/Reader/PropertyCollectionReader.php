<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Documentation\PropertyCollectionDescription;
use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class PropertyCollectionReader
{
    /**
     * @var PropertyReader
     */
    private $propertyReader;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    public function __construct(PropertyReader $propertyReader, PropertyInfoExtractorInterface $extractor)
    {
        $this->propertyReader = $propertyReader;
        $this->extractor      = $extractor;
    }

    public function read(FacadeInterface $facade, PropertyDescription $parent = null) : PropertyCollectionDescription
    {
        $collectionDescription = new PropertyCollectionDescription();

        if ($facade instanceof CollectionFacade) {
            $collectionDescription->setCollection(true);

            if ($value = $facade->first()) {
                foreach ($this->read($value, $parent)->all() as $propertyDescription) {
                    $collectionDescription->add($propertyDescription);
                }
            }

            return $collectionDescription;
        }

        $class      = get_class($facade);
        $properties = $this->extractor->getProperties($class);
        $accessor   = PropertyAccess::createPropertyAccessor();
        foreach ($properties ?? [] as $property) {
            $propertyDescription = $this->propertyReader->read($class, $property, $parent);
            $value               = $accessor->getValue($facade, $property);

            if ($value instanceof FacadeInterface) {
                $children = $this->read($value, $propertyDescription);
                $propertyDescription->setChildren($children);

                // Transforms a FacadeInterface (object) into an array if that facade is a collection.
                if ($children->isCollection()) {
                    $propertyDescription->setTypes([
                        new TypeDescription('array', false, CollectionFacade::class),
                    ]);
                }
            }

            $collectionDescription->add($propertyDescription);
        }

        $collectionDescription->sort();

        return $collectionDescription;
    }
}