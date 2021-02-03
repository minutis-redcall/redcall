<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\PropertyDescription;
use Bundles\ApiBundle\Model\Documentation\TypeDescription;
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

    public function __construct(DocblockFetcher $docblockFetcher, PropertyInfoExtractorInterface $extractor)
    {
        $this->docblockFetcher = $docblockFetcher;
        $this->extractor       = $extractor;
    }

    public function fetch(string $class, string $property) : PropertyDescription
    {
        $description = new PropertyDescription();
        $description->setName($property);

        $types = $this->extractor->getTypes($class, $property);
        foreach ($types ?? [] as $type) {
            $type = new TypeDescription($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), $type->isCollection(), $type->getCollectionKeyType(), $type->getCollectionValueType());
            $description->addType($type);
        }

        dump($this->extractor->getLongDescription($class, $property));
        dd($this->extractor->getShortDescription($class, $property));

        return $description;
    }

    /*
                properties:
                private $name;
                private $type;
                private $description;
                private $constraints = [];
     */
}