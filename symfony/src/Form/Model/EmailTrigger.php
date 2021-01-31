<?php

namespace App\Form\Model;

use App\Entity\Communication;
use App\Entity\Message;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailTrigger extends BaseTrigger
{
    public function __construct()
    {
        $this->setType(Communication::TYPE_EMAIL);
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (mb_strlen(strip_tags($this->getMessage())) > Message::MAX_LENGTH_EMAIL) {
            $context->buildViolation('form.communication.errors.too_large_sms')
                    ->atPath('message')
                    ->addViolation();
        }

        if (!$this->getSubject()) {
            $context->buildViolation('form.communication.errors.no_subject')
                    ->atPath('subject')
                    ->addViolation();
        }
    }
}