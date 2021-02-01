<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\PropertyCollectionDescription;
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

    public function fetch(string $class/*, Facade $decorates*/) : PropertyCollectionDescription
    {
        $collection = new PropertyCollectionDescription();

        /*
         * TODO
         * Properties should be based on the example, not on the class
         * - The class may contain a FacadeInterface
         * - The example has an instance from which we can get real class name
         * Property names should be separated by dots . or []. (if collection)
         */

        $properties = $this->extractor->getProperties($class);
        foreach ($properties as $property) {
            $collection->add(
                $this->propertyFetcher->fetch($class, $property)
            );
        }

        return $collection;
    }
}