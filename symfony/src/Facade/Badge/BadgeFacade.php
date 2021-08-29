<?php

namespace App\Facade\Badge;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BadgeFacade implements FacadeInterface
{
    /**
     * An unique identifier for the badge.
     *
     * You can use a random UUID, a name or the same identifier as in your own application.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $externalId;

    /**
     * The badge's category, if any.
     *
     * On the UX, sorting badges by category may ease reading.
     * - first aider level 1, 2, 3 are all first aid trainings
     * - ambulance, car and truck are vehicles
     *
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $categoryExternalId;

    /**
     * Badge's name.
     *
     * Badge name should be small because it is rendered everywhere where a volunteer is rendered.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $name;

    /**
     * Badge's description.
     *
     * Badge description should help to understand what it is/means.
     *
     * @Assert\Length(max="255")
     *
     * @var string|null
     */
    protected $description;

    /**
     * Badge's visibility.
     *
     * You can choose whether badge should be visible by default on the platform. All in all, a visible badge is meant
     * to be used often, while others can be used in a less convenient way when needed very occasionally.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $visibility = false;

    /**
     * Badge's rendering prioirty.
     *
     * Badge can be visually prioritized, so when rendering a list of badges, you can control which ones to render
     * first. If the badge is categorized, priority will affect the badge's position in its category. Otherwise,
     * badge will be rendered after all categories and prioritized against other non-categorized badges.
     *
     * @Assert\Range(min="0", max="1000")
     *
     * @var int|null
     */
    protected $renderingPriority = 0;

    /**
     * Badge's triggering priority.
     *
     * Badge can prioritize sending of triggers to volunteers having key skills. For example, calling first-aiders
     * before marauders.
     *
     * @Assert\Range(min="0", max="1000")
     *
     * @var int|null
     */
    protected $triggeringPriority = 500;

    /**
     * The badge of the above level, which covers this one.
     *
     * A badge may have a "parent" badge, for example a "car driver" (VL)
     * has "ambulance driver" (VPSP) as parent, because if someone has an
     * ambulance driving license, (s)he must have a car driving license as well.
     *
     * Another example, If someone has "advanced first aider" level (PSE2), (s)he
     * must have "intermediate first aider" level (PSE1).
     *
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $coveredByExternalId;

    /**
     * The badge synonym.
     *
     * All volunteers having that badge will be considered as having the
     * synonym badge, and that badge will disappear from interfaces.
     *
     * For example, if you have two badges "Car Driver" and "Vehicle Driver", you
     * can set "Car Driver" as synonym for "Vehicle Driver", and only "Car Driver"
     * will appear on user interfaces. Filtering out "Car Driver" will also trigger
     * people having the "Vehicle Driver" badge.
     *
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $replacedByExternalId;

    /**
     * Whether the badge is locked or not.
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
     * Whether the badge is enabled or not.
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

        $facade->externalId  = 'demo-car';
        $facade->name        = 'CD';
        $facade->description = 'Car Driver';
        $facade->visibility  = true;
        $facade->locked      = false;
        $facade->enabled     = true;

        return $facade;
    }

    public function getExternalId() : ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : BadgeFacade
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getCategoryExternalId() : ?string
    {
        return $this->categoryExternalId;
    }

    public function setCategoryExternalId(?string $categoryExternalId) : BadgeFacade
    {
        $this->categoryExternalId = $categoryExternalId;

        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : BadgeFacade
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : BadgeFacade
    {
        $this->description = $description;

        return $this;
    }

    public function getVisibility() : ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(?bool $visibility) : BadgeFacade
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getRenderingPriority() : ?int
    {
        return $this->renderingPriority;
    }

    public function setRenderingPriority(?int $renderingPriority) : BadgeFacade
    {
        $this->renderingPriority = $renderingPriority;

        return $this;
    }

    public function getTriggeringPriority() : ?int
    {
        return $this->triggeringPriority;
    }

    public function setTriggeringPriority(?int $triggeringPriority) : BadgeFacade
    {
        $this->triggeringPriority = $triggeringPriority;

        return $this;
    }

    public function getCoveredByExternalId() : ?string
    {
        return $this->coveredByExternalId;
    }

    public function setCoveredByExternalId(?string $coveredByExternalId) : BadgeFacade
    {
        $this->coveredByExternalId = $coveredByExternalId;

        return $this;
    }

    public function getReplacedByExternalId() : ?string
    {
        return $this->replacedByExternalId;
    }

    public function setReplacedByExternalId(?string $replacedByExternalId) : BadgeFacade
    {
        $this->replacedByExternalId = $replacedByExternalId;

        return $this;
    }

    public function getLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : BadgeFacade
    {
        $this->locked = $locked;

        return $this;
    }

    public function getEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled) : BadgeFacade
    {
        $this->enabled = $enabled;

        return $this;
    }
}