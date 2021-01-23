<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\ConstraintViolation;

class ViolationFacade
{
    /**
     * @var string
     */
    private $propertyPath;

    /**
     * @var string|null
     */
    private $invalidValue;

    /**
     * @var string
     */
    private $message;

    public function __construct(ConstraintViolation $violation)
    {
        $this->propertyPath = $violation->getPropertyPath();
        $this->invalidValue = $violation->getInvalidValue();
        $this->message      = $violation->getMessage();
    }

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        $facade = new self;

        $facade->propertyPath = 'firstName';
        $facade->invalidValue = null;
        $facade->message      = 'This field cannot be empty.';

        return $facade;
    }

    public function getPropertyPath() : ?string
    {
        return $this->propertyPath;
    }

    public function getInvalidValue() : ?string
    {
        return $this->invalidValue;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}
