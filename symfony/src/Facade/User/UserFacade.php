<?php

namespace App\Facade\User;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UserFacade implements FacadeInterface
{
    /**
     * User's identifier, generally this is the email (s)he used to sign-up to the platform. When using
     * external connectors, it may also be the email tied to the external resource (eg. a Red Cross volunteer).
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max=64)
     * @Assert\Email
     *
     * @SerializedName("email")
     *
     * @var string|null
     */
    protected $identifier;

    /**
     * Identifier of the volunteer tied to that user.
     *
     * Users are only resources whose purpose is authentication and authorization,
     * but they should be attached to physical persons (volunteers) in order to
     * trigger people.
     *
     * Having a volunteer tied to every user helps to see real previews and
     * use "Test on me" buttons, as volunteers are required to build
     * communications.
     *
     * Note: if a user will not trigger people (e.g. an administrator, or a developer,
     * but not an operations manager) then it is not necessary to attach a volunteer
     * to it.
     *
     * In order to unbind a volunteer from a user, use boolean false.
     *
     * @Assert\Length(max = 64)
     *
     * @var string|bool|null
     */
    protected $volunteerExternalId;

    /**
     * When registering, every user receive a verification email. User''s email is considered valid once user clicked
     * on the link it contains. Non-verified users cannot connect to the platform.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $verified;

    /**
     * Anyone can subscribe to the platform, but only the ones trusted (activated manually by an administrator)
     * can access the provided tools.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $trusted;

    /**
     * A developer can integrate RedCall APIs and access technical features.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $developer;

    /**
     * An administrator can trust new users and configure the platform.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $administrator;

    /**
     * A root has the same capabilities as an administrator, but can switch between the different platforms
     * (eg. France, Spain, ...), and can also change all resources' platform.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $root;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->setIdentifier('john.doe@example.org');
        $facade->setVerified(true);
        $facade->setTrusted(true);
        $facade->setDeveloper(false);
        $facade->setAdministrator(false);
        $facade->setRoot(false);

        return $facade;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier) : UserFacade
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return bool|string|null
     */
    public function getVolunteerExternalId()
    {
        return $this->volunteerExternalId;
    }

    /**
     * @param string|bool|null $volunteerExternalId
     */
    public function setVolunteerExternalId($volunteerExternalId) : UserFacade
    {
        $this->volunteerExternalId = $volunteerExternalId;

        return $this;
    }

    public function isVerified() : ?bool
    {
        return $this->verified;
    }

    public function setVerified(?bool $verified) : UserFacade
    {
        $this->verified = $verified;

        return $this;
    }

    public function isTrusted() : ?bool
    {
        return $this->trusted;
    }

    public function setTrusted(?bool $trusted) : UserFacade
    {
        $this->trusted = $trusted;

        return $this;
    }

    public function isDeveloper() : ?bool
    {
        return $this->developer;
    }

    public function setDeveloper(?bool $developer) : UserFacade
    {
        $this->developer = $developer;

        return $this;
    }

    public function isAdministrator() : ?bool
    {
        return $this->administrator;
    }

    public function setAdministrator(?bool $administrator) : UserFacade
    {
        $this->administrator = $administrator;

        return $this;
    }

    public function isRoot() : ?bool
    {
        return $this->root;
    }

    public function setRoot(?bool $root) : UserFacade
    {
        $this->root = $root;

        return $this;
    }
}