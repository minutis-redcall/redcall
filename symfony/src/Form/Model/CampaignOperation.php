<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CampaignOperation
{
    /**
     * @var int
     */
    public $structureExternalId;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"Create"})
     * @Assert\Length(max=255, groups={"Create"})
     */
    public $name;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"Use"})
     * @Assert\Length(max=64, groups={"Use"})
     */
    public $operationExternalId;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"Create"})
     * @Assert\Length(max=64, groups={"Create"})
     */
    public $ownerExternalId;

    /**
     * @var string[]
     */
    public $choices = [];

    /**
     * @var Campaign
     */
    public $campaign;
}