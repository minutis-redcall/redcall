<?php

namespace App\Facade\User;

use App\Facade\Resource\ResourceReferenceFacadeInterface;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UserReferenceFacade implements ResourceReferenceFacadeInterface
{
    /**
     * The identifier you've set to identify a user.
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max = 64)
     *
     * @SerializedName("email")
     *
     * @var string
     */
    private $externalId;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->externalId = sprintf('user-%d@example.org', rand() % 100);

        return $facade;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : UserReferenceFacade
    {
        $this->externalId = $externalId;

        return $this;
    }
}