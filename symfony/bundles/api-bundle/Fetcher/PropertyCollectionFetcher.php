<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Documentation\PropertyCollectionDescription;
use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class PropertyCollectionFetcher
{
    /**
     * @var PropertyFetcher
     */
    private $propertyFetcher;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    public function __construct(PropertyFetcher $propertyFetcher, PropertyInfoExtractorInterface $extractor)
    {
        $this->propertyFetcher = $propertyFetcher;
        $this->extractor       = $extractor;
    }

    public function fetch(FacadeInterface $facade, PropertyDescription $parent = null) : PropertyCollectionDescription
    {
        $class      = get_class($facade);
        $collection = new PropertyCollectionDescription();
        $properties = $this->extractor->getProperties($class);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $property) {
            $description = $this->propertyFetcher->fetch($class, $property);

            if ($parent) {
                $description->setParent($parent);
            }

            $value = $accessor->getValue($facade, $property);

            if (is_iterable($value)) {
                $description->setCollection(true);
            }

            if ($value instanceof FacadeInterface) {
                $description->setChildren(
                    $this->fetch($value, $description)
                );
            }

            $collection->add($description);
        }

        return $collection;
    }
}