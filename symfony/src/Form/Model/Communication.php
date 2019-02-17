<?php

namespace App\Form\Model;

use App\Entity\Message;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Communication
{
    /**
     * @var string
     *
     * @Assert\Length(max=255, groups={"label_edition"})
     */
    public $label;

    /**
     * @var string
     *
     * @Assert\Choice(choices = {
     *     \App\Entity\Communication::TYPE_ALERT,
     *     \App\Entity\Communication::TYPE_WEB
     * })
     */
    public $type = \App\Entity\Communication::TYPE_ALERT;

    /**
     * @var Collection
     *
     * @Assert\Count(min="1", minMessage="form.campaign.errors.volunteers.min")
     */
    public $volunteers;

    /**
     * @var string
     *
     * @Assert\NotNull(message="form.campaign.errors.message.empty")
     * @Assert\Length(min=Message::MIN_LENGTH, max=Message::MAX_LENGTH)
     */
    public $message;

    /**
     * @var array
     * @Assert\Count(max=10)
     */
    public $answers;

    /**
     * @var boolean
     */
    public $geoLocation;

    /**
     * @var boolean
     */
    public $multipleAnswer;

    /**
     * @param ExecutionContextInterface $context
     * @param                           $payload
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->type === \App\Entity\Communication::TYPE_WEB && count($this->answers) == 0) {
            $context->buildViolation('form.communication.errors.web_message')
                    ->atPath('type')
                    ->addViolation();
        }
    }
}