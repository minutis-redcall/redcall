<?php

namespace App\Form\Model;

use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Communication implements \JsonSerializable
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
     *     \App\Entity\Communication::TYPE_SMS,
     *     \App\Entity\Communication::TYPE_CALL,
     *     \App\Entity\Communication::TYPE_EMAIL
     * })
     */
    public $type = \App\Entity\Communication::TYPE_SMS;

    /**
     * @var array
     */
    public $audience;

    /**
     * @var string
     *
     * @Assert\Length(max=80)
     */
    public $subject;

    /**
     * @var string
     *
     * @Assert\NotNull(message="form.campaign.errors.message.empty")
     * @Assert\Length(min=Message::MIN_LENGTH)
     */
    public $message;

    /**
     * @var array
     *
     * @Assert\Count(max=9)
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
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->type !== \App\Entity\Communication::TYPE_SMS) {
            if ($this->geoLocation) {
                $context->buildViolation('form.communication.errors.email_geolocation')
                    ->atPath('geoLocation')
                    ->addViolation();
            }
        }

        if ($this->type === \App\Entity\Communication::TYPE_CALL) {
            if ($this->multipleAnswer) {
                $context->buildViolation('form.communication.errors.call_multiple')
                    ->atPath('multipleAnswer')
                    ->addViolation();
            }
        }

        if ($this->type === \App\Entity\Communication::TYPE_SMS) {
            if (mb_strlen($this->message) > Message::MAX_LENGTH_SMS) {
                $context->buildViolation('form.communication.errors.too_large_sms')
                    ->atPath('message')
                    ->addViolation();
            }
        }

        if ($this->type === \App\Entity\Communication::TYPE_EMAIL) {
            if (!$this->subject) {
                $context->buildViolation('form.communication.errors.no_subject')
                    ->atPath('subject')
                    ->addViolation();
            }

            if (mb_strlen($this->message) > Message::MAX_LENGTH_EMAIL) {
                $context->buildViolation('form.communication.errors.too_large_email')
                        ->atPath('message')
                        ->addViolation();
            }
        }

        if (0 === count($this->audience)) {
            $context->buildViolation('form.campaign.errors.volunteers.min')
                    ->addViolation();
        }
    }

    public function jsonSerialize(): string
    {
        return get_object_vars($this);
    }

    public function jsonUnserialize(string $json)
    {
        foreach (json_decode($json, true) as $prop => $value) {
            $this->{$prop} = $value;
        }
    }
}