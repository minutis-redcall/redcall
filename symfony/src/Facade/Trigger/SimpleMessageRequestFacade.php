<?php

namespace App\Facade\Trigger;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SimpleMessageRequestFacade implements FacadeInterface
{
    /**
     * Internal email of the volunteer for which we are sending the trigger.
     *
     * In order to bill the right structure, we need to know who requested the trigger.
     *
     * @Assert\Email
     *
     * @var string|null
     */
    protected $senderInternalEmail = null;

    /**
     * Message to send.
     *
     * Keep it short (< 300 chars) to improve its delivery rate.
     *
     * @Assert\NotNull()
     * @Assert\Length(min=1, minMessage="form.campaign.errors.message.empty")
     *
     * @var string
     */
    protected $message = null;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade                      = new self;
        $facade->senderInternalEmail = 'roger.rabbit@croix-rouge.fr';
        $facade->message             = 'Recherche d\'effectifs pour tenir CHU en 3x8 la semaine prochaine, merci de vous inscire sur https://example.com';

        return $facade;
    }

    public function getSenderInternalEmail() : ?string
    {
        return $this->senderInternalEmail;
    }

    public function setSenderInternalEmail(?string $senderInternalEmail) : SimpleMessageRequestFacade
    {
        $this->senderInternalEmail = $senderInternalEmail;

        return $this;
    }

    public function getMessage() : ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message) : SimpleMessageRequestFacade
    {
        $this->message = $message;

        return $this;
    }
}