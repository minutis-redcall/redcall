<?php

namespace App\Form\Model;

use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SmsTrigger extends BaseTrigger
{
    /**
     * @var boolean
     */
    private $geoLocation = false;

    public function __construct()
    {
        $this->setType(Communication::TYPE_SMS);
    }

    /**
     * @return bool
     */
    public function isGeoLocation() : bool
    {
        return $this->geoLocation;
    }

    /**
     * @param bool $geoLocation
     *
     * @return BaseTrigger
     */
    public function setGeoLocation(bool $geoLocation) : BaseTrigger
    {
        $this->geoLocation = $geoLocation;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        parent::validate($context, $payload);

        foreach ($this->getAnswers() as $answer) {
            if (mb_strlen($answer) > Choice::MAX_LENGTH_SMS) {
                $context->buildViolation('form.communication.errors.too_large_choice')
                        ->atPath('answers')
                        ->addViolation();
            }
        }

        if (mb_strlen($this->getMessage()) > Message::MAX_LENGTH_SMS) {
            $context->buildViolation('form.communication.errors.too_large_sms')
                    ->atPath('message')
                    ->addViolation();
        }
    }
}