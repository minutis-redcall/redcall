<?php

namespace App\Facade\Phone;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class PhoneFacade implements FacadeInterface
{
    /**
     * Whether this number is currently selected by the volunteer.
     *
     * @var bool
     */
    protected $preferred;

    /**
     * The phone number in the international format E.164.
     *
     * @var string
     */
    protected $e164;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->preferred = true;
        $facade->e164      = '+33612345678';

        return $facade;
    }

    public function isPreferred() : bool
    {
        return $this->preferred;
    }

    public function setPreferred(bool $preferred) : PhoneFacade
    {
        $this->preferred = $preferred;

        return $this;
    }

    public function getE164() : string
    {
        return $this->e164;
    }

    public function setE164(string $e164) : PhoneFacade
    {
        $this->e164 = $e164;

        return $this;
    }
}