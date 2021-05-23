<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\ConstraintViolation;

class ViolationFacade implements FacadeInterface
{
    /**
     * Property having an invalid value.
     *
     * @var string
     */
    private $propertyPath;

    /**
     * The invalid value.
     *
     * @var string|null
     */
    private $invalidValue;

    /**
     * An error message describing the violation.
     *
     * @var string
     */
    private $message;

    public function __construct(ConstraintViolation $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->invalidValue = null;
        $this->message      = $violation->getMessage();

        $invalidValue = $violation->getInvalidValue();
        if (!is_object($invalidValue) || $invalidValue instanceof FacadeInterface) {
            $this->invalidValue = $invalidValue;
        }
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        return new static(
            new ConstraintViolation('The amount should be greater than 1', null, [], 'product', 'price', -5)
        );
    }

    public function getPropertyPath() : ?string
    {
        return $this->propertyPath;
    }

    public function getInvalidValue()
    {
        return $this->invalidValue;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}
