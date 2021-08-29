<?php

namespace App\Facade\Badge;

use App\Facade\Resource\ResourceReferenceFacadeInterface;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BadgeReferenceFacade implements ResourceReferenceFacadeInterface
{
    /**
     * The identifier you've set to identify a badge.
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    private $externalId;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->externalId = sprintf('badge-%d', rand() % 100);

        return $facade;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : BadgeReferenceFacade
    {
        $this->externalId = $externalId;

        return $this;
    }
}