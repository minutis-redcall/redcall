<?php

namespace App\Form\Model;

use App\Entity\Communication;
use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CallTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->setType(Communication::TYPE_CALL);
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (mb_strlen($this->getMessage()) > Message::MAX_LENGTH_CALL) {
            $context->buildViolation('form.communication.errors.too_large_sms')
                ->atPath('message')
                ->addViolation();
        }
    }
}