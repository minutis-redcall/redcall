<?php

namespace App\Facade\Admin\Category;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryFacade implements FacadeInterface
{
    /**
     * An unique identifier for the category.
     *
     * A random UUID, a name, or the same identifier as in your own application.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    protected $externalId;

    /**
     * A name for the given category.
     * Name should be human readable as it may be used in user interfaces.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    protected $name;

    /**
     * The category rendering priority.
     * You may want to see "First Aid" category before "Vehicles" in the audience selection form,
     * or when listing volunteers badge.
     *
     * @Assert\Range(min = 0, max = 1000)
     *
     * @var int|null
     */
    protected $priority = 500;

    /**
     * Whether the category is locked or not.
     *
     * A "locked" category cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $locked;

    /**
     * Whether the category is enabled or not.
     *
     * RedCall resources (categories, badges, structures, volunteers) may have relations with
     * other sensible parts of the application (triggers, communications, messages, answers, etc.),
     * so it may be safer to disable them instead of deleting them and creating database inconsistencies.
     *
     * In order to comply with the General Data Protection Regulation (GDPR), resources containing
     * private information can be anonymized.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $enabled;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->externalId = 'vehicles';
        $facade->name       = 'Vehicles';
        $facade->priority   = 42;
        $facade->locked     = false;
        $facade->enabled    = true;

        return $facade;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : CategoryFacade
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : CategoryFacade
    {
        $this->name = $name;

        return $this;
    }

    public function getPriority() : int
    {
        return $this->priority;
    }

    public function setPriority(int $priority) : CategoryFacade
    {
        $this->priority = $priority;

        return $this;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : CategoryFacade
    {
        $this->locked = $locked;

        return $this;
    }

    public function isEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled) : CategoryFacade
    {
        $this->enabled = $enabled;

        return $this;
    }
}