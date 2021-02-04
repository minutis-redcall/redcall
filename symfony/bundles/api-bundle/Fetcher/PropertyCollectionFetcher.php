<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Documentation\PropertyCollectionDescription;
use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
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
        $collectionDescription = new PropertyCollectionDescription();

        if ($facade instanceof CollectionFacade) {
            $collectionDescription->setCollection(true);

            if ($value = $facade->first()) {
                foreach ($this->fetch($value, $parent)->all() as $propertyDescription) {
                    $collectionDescription->add($propertyDescription);
                }
            }

            return $collectionDescription;
        }

        $class      = get_class($facade);
        $properties = $this->extractor->getProperties($class);
        $accessor   = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $property) {
            $propertyDescription = $this->propertyFetcher->fetch($class, $property, $parent);
            $value               = $accessor->getValue($facade, $property);

            if ($value instanceof FacadeInterface) {
                $propertyDescription->setChildren(
                    $this->fetch($value, $propertyDescription)
                );
            }

            $collectionDescription->add($propertyDescription);
        }

        return $collectionDescription;
    }
}