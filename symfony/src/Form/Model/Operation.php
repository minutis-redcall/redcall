<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Operation
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=64)
     */
    public $operationExternalId;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=64)
     */
    public $ownerExternalId;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    public $name;

    public $choices = [];
}