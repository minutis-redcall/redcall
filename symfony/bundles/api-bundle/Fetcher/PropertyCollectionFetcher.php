<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Documentation\PropertyCollectionDescription;
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

    public function fetch(FacadeInterface $facade) : PropertyCollectionDescription
    {
        $class      = get_class($facade);
        $collection = new PropertyCollectionDescription();
        $properties = $this->extractor->getProperties($class);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $property) {
            $description = $this->propertyFetcher->fetch($class, $property);

            // Based on the example ?
            $value = $accessor->getValue($facade, $property);
            if ($value instanceof FacadeInterface) {
                // ...
            }

            // Or based on the doc only, but it can contain interfaces and not implementations?
            foreach ($description->getTypes() as $type) {
                if ($type->isFacade()) {
                    // ...
                }
            }

            // Or both?
            foreach ($description->getTypes() as $type) {
                if ($type->isFacade()) {
                    if (interface_exists($type->getClassName())) {
                        // Cannot use the doc
                        $value = $accessor->getValue($facade, $property);
                        // ...
                    } else {
                        //

                    }
                }
            }

            $collection->add($description);
        }

        return $collection;
    }
}