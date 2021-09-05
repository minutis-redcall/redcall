<?php

namespace App\Facade\Phone;

use App\Contract\PhoneInterface;
use App\Validator\Constraints as CustomAssert;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @CustomAssert\Phone
 */
class PhoneFacade implements PhoneInterface, FacadeInterface
{
    /**
     * Whether this number is currently selected by the volunteer.
     *
     * @Assert\NotNull
     * @Assert\Choice(choices={false, true})
     *
     * @var bool
     */
    protected $preferred = true;

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