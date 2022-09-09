<?php

namespace App\Form\Model;

use App\Entity\Campaign as CampaignEntity;
use Symfony\Component\Validator\Constraints as Assert;

class Campaign
{
    const CREATE_OPERATION = 'create';
    const USE_OPERATION    = 'use';

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=255, groups={"label_edition", "Default"})
     */
    public $label;

    /**
     * @var int
     *
     * @Assert\NotNull(message="form.campaign.errors.type.empty", groups={"color_edition", "Default"})
     * @Assert\Choice({
     *     CampaignEntity::TYPE_GREEN,
     *     CampaignEntity::TYPE_LIGHT_ORANGE,
     *     CampaignEntity::TYPE_DARK_ORANGE,
     *     CampaignEntity::TYPE_RED
     * }, groups={"color_edition", "Default"})
     */
    public $type;

    /**
     * @var BaseTrigger
     *
     * @Assert\Valid
     */
    public $trigger;

    /**
     * @var bool
     */
    public $hasOperation;

    /**
     * @var string
     */
    public $createOperation;

    /**
     * @var Operation
     *
     * @Assert\Valid
     */
    public $operation;

    public function __construct(BaseTrigger $trigger)
    {
        $this->type                = CampaignEntity::TYPE_GREEN;
        $this->label               = '';
        $this->trigger             = $trigger;
        $this->hasOperation        = false;
        $this->createOperation     = self::CREATE_OPERATION;
        $this->operation           = new Operation();
        $this->operation->campaign = $this;
    }
}