<?php

namespace App\Form\Model;

use App\Entity\Communication;
use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SmsTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->setType(Communication::TYPE_SMS);
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (mb_strlen($this->getMessage()) > Message::MAX_LENGTH_SMS) {
            $context->buildViolation('form.communication.errors.too_large_sms')
                    ->atPath('message')
                    ->addViolation();
        }
    }
}