<?php

namespace Bundles\ApiBundle\Reader;

use Bundles\ApiBundle\Model\Documentation\ConstraintDescription;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Validator\Constraint;

class ConstraintReader
{
    /**
     * @var PropertyInfoExtractorInterface
     */
    private $extractor;

    public function __construct(PropertyInfoExtractorInterface $extractor)
    {
        $this->extractor = $extractor;
    }

    public function read(Constraint $constraint) : ConstraintDescription
    {
        $description = new ConstraintDescription();

        $class     = get_class($constraint);
        $camelName = substr($class, strrpos($class, '\\') + 1);
        $spaceName = $this->camelToSpace($camelName);
        $description->setName($spaceName);

        // Get all constraint properties (options)
        $configuration = $this->extractor->getProperties($class);
        $values        = [];
        foreach ($configuration as $parameter) {
            try {
                $reflector = new \ReflectionProperty($class, $parameter);
                $reflector->setAccessible(true);
                $values[$parameter] = $reflector->getValue($constraint);
            } catch (\ReflectionException $e) {
            }
        }

        // Placeholders are all scalar properties
        $placeholders = [];
        foreach (array_map('is_scalar', $values) as $key => $value) {
            $placeholders[sprintf('{{ %s }}', $key)] = $value;
        }

        // Create options after replacing placeholders by real values
        $options = [];
        foreach ($values as $key => $value) {

            // ---------------------------------------------------------------------
            // TODO: once App Engine supports PHP 8, this should be changed:
            //       we need to ignore constraints that have their default value
            if (null === $value || false !== stripos($key, 'message')) {
                continue;
            }
            // ---------------------------------------------------------------------

            if (is_string($value)) {
                $options[$this->camelToSpace($key)] = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $value
                );
            } else {
                $options[$key] = $value;
            }
        }

        $description->setOptions($options);

        return $description;
    }

    private function camelToSpace(string $camelValue)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $camelValue));
    }
}