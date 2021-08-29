<?php

namespace App\Facade\Badge;

use App\Facade\Resource\BadgeResourceFacade;
use App\Facade\Resource\CategoryResourceFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;

class BadgeReadFacade extends BadgeFacade
{
    /**
     * The badge category.
     *
     * Badge can be categorized (ex: truck-driver and car-driver would be in a Vehicles category).
     *
     * @var CategoryResourceFacade|null
     */
    protected $category;

    /**
     * List of badges that are covered by this one.
     *
     * A "car driver" badge can be covered by a "truck driver" badge, because if someone is able to
     * ride a truck, he should be able to ride a car.
     *
     * @var BadgeResourceFacade[]
     */
    protected $coveredBy;

    /**
     * List of badges that this one covers.
     *
     * A "chief first-aider" badge could cover an "experienced first-aider" and "newbie first-aider" badges.
     *
     * @var BadgeResourceFacade[]
     */
    protected $covers;

    /**
     * A badge can be replaced by a synonym badge.
     *
     * If your application has similar badges, for example automobile-driver and car-driver, then you can set
     * car-driver as synonym for automobile-driver badge. The automobile-driver badge will be hidden in the
     * application, and all people having the automobile-driver badge will be selected when using the car-driver badge.
     *
     * @var BadgeResourceFacade|null
     */
    protected $replacedBy;

    /**
     * List of badges a badge replaces.
     *
     * It's the reverse side of the property above. For example, if automobile-driver is replaced by car-driver, then
     * car-driver will have automobile-driver there. In order to handle pagination and prevent against nesting loops,
     * the list of badges have been put in another API call.
     *
     * @var BadgeResourceFacade[]
     */
    protected $replaces;

    /**
     * Number of people having the given badge.
     *
     * @var int
     */
    protected $peopleCount = 0;


    /**
     * Whether the badge is locked or not.
     *
     * A "locked" category cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
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
     * @var bool|null
     */
    protected $enabled;

    public function __construct()
    {
        $this->coveredBy = new CollectionFacade();
        $this->covers    = new CollectionFacade();
        $this->replaces  = new CollectionFacade();
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $facade->setCategory(
            CategoryResourceFacade::getExample()
        );

        $facade->addCoveredBy(
            BadgeResourceFacade::getExample()
        );

        $facade->locked  = false;
        $facade->enabled = true;

        return $facade;
    }

    public function getCategory() : ?CategoryResourceFacade
    {
        return $this->category;
    }

    public function setCategory(?CategoryResourceFacade $category) : BadgeReadFacade
    {
        $this->category = $category;

        return $this;
    }

    public function getCoveredBy() : CollectionFacade
    {
        return $this->coveredBy;
    }

    public function addCoveredBy(BadgeResourceFacade $facade) : BadgeReadFacade
    {
        $this->coveredBy[] = $facade;

        return $this;
    }

    public function getCovers() : CollectionFacade
    {
        return $this->covers;
    }

    public function setCovers($covers)
    {
        $this->covers = $covers;

        return $this;
    }

    public function addCovers(BadgeResourceFacade $facade) : BadgeReadFacade
    {
        $this->covers[] = $facade;

        return $this;
    }

    public function getReplacedBy() : ?BadgeResourceFacade
    {
        return $this->replacedBy;
    }

    public function setReplacedBy(?BadgeResourceFacade $replacedBy) : BadgeReadFacade
    {
        $this->replacedBy = $replacedBy;

        return $this;
    }

    public function getReplaces() : CollectionFacade
    {
        return $this->replaces;
    }

    public function addReplaces(BadgeResourceFacade $facade) : BadgeReadFacade
    {
        $this->replaces[] = $facade;

        return $this;
    }

    public function getPeopleCount() : int
    {
        return $this->peopleCount;
    }

    public function setPeopleCount(int $peopleCount) : BadgeReadFacade
    {
        $this->peopleCount = $peopleCount;

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
