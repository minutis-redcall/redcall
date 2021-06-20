<?php

namespace App\Facade\Volunteer;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class VolunteerReferenceFacade implements FacadeInterface
{
    /**
     * The identifier you've set to identify a volunteer.
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

        $facade->externalId = sprintf('volunteer-%d', rand() % 100);

        return $facade;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : VolunteerReferenceFacade
    {
        $this->externalId = $externalId;

        return $this;
    }
}