<?php

namespace App\Facade\Admin\Badge;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

abstract class BadgeFacade implements FacadeInterface
{
    /**
     * An unique identifier for the badge.
     *
     * You can use a random UUID or the same identifier as in your own application.
     *
     * @Assert\NotBlank
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    protected $externalId;

    /**
     * Badge's name.
     *
     * Badge name should be small because it is rendered everywhere where a volunteer is rendered.
     *
     * @Assert\NotBlank
     * @Assert\Length(max = 64)
     *
     * @var string
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

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade
            ->setExternalId('demo-truck')
            ->setName('CD')
            ->setDescription('Truck Driver')
            ->setVisibility(true);

        return $facade;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : BadgeFacade
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getName() : string
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
}