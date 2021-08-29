<?php

namespace App\Facade\Generic;

use App\Enum\Platform;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PlatformFacade implements FacadeInterface
{
    /**
     * @Assert\NotBlank
     * @Assert\Choice(choices={Platform::FR, Platform::ES})
     *
     * @var string
     */
    protected $platform;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->platform = Platform::FR;

        return $facade;
    }

    public function getPlatform() : string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform) : PlatformFacade
    {
        $this->platform = $platform;

        return $this;
    }
}