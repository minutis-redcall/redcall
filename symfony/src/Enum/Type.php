<?php

namespace App\Enum;

use App\Form\Flow\CallTriggerFlow;
use App\Form\Flow\EmailTriggerFlow;
use App\Form\Flow\SmsTriggerFlow;
use App\Form\Model\BaseTrigger;
use App\Form\Model\CallTrigger;
use App\Form\Model\EmailTrigger;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CallTriggerType;
use App\Form\Type\EmailTriggerType;
use App\Form\Type\SmsTriggerType;
use MyCLabs\Enum\Enum;

/**
 * @method static $this SMS
 * @method static $this CALL
 * @method static $this EMAIL
 */
final class Type extends Enum
{
    private const SMS   = 'sms';
    private const CALL  = 'call';
    private const EMAIL = 'email';

    public function getFormType() : string
    {
        switch ($this->value) {
            case self::SMS:
                return SmsTriggerType::class;
            case self::CALL:
                return CallTriggerType::class;
            case self::EMAIL:
                return EmailTriggerType::class;
        }
    }

    public function getFormData() : BaseTrigger
    {
        switch ($this->value) {
            case self::SMS:
                return new SmsTrigger();
            case self::CALL:
                return new CallTrigger();
            case self::EMAIL:
                return new EmailTrigger();
        }
    }

    public function getFormFlow() : string
    {
        switch ($this->value) {
            case self::SMS:
                return SmsTriggerFlow::class;
            case self::CALL:
                return CallTriggerFlow::class;
            case self::EMAIL:
                return EmailTriggerFlow::class;
        }
    }

    public function getFormView() : string
    {
        switch ($this->value) {
            case self::SMS:
                return 'new_communication/form_sms.html.twig';
            case self::CALL:
                return 'new_communication/form_call.html.twig';
            case self::EMAIL:
                return 'new_communication/form_email.html.twig';
        }
    }

    public function isSms() : bool
    {
        return self::SMS === $this->value;
    }

    public function isCall() : bool
    {
        return self::CALL === $this->value;
    }

    public function isEmail() : bool
    {
        return self::EMAIL === $this->value;
    }
}