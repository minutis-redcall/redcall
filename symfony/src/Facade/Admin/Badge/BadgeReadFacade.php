<?php

namespace App\Facade\Admin\Badge;

use App\Facade\Admin\Category\CategoryFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeReadFacade extends BadgeFacade
{
    /**
     * The badge category.
     *
     * Badge can be categorized (ex: truck-driver and car-driver would be in a Vehicles category).
     *
     * @var CategoryFacade|null
     */
    protected $category;

    /**
     * Number of badges that are covered by this one.
     *
     * A badge can be covered by other badges.
     * A "car driver" badge can be covered by a "truck driver" badge, because if someone is able to
     * ride a truck, he should be able to ride a car.
     *
     * @var int
     */
    protected $coveredBy = 0;

    /**
     * Number of badges that this one covers.
     *
     * A badge can cover other badges.
     * A "chief first-aider" badge could cover an "experienced first-aider" and "newbie first-aider" badges.
     *
     * @var int
     */
    protected $coversCount = 0;

    /**
     * A badge can be replaced by a synonym badge.
     *
     * If your application has similar badges, for example automobile-driver and car-driver, then you can set
     * car-driver as synonym for automobile-driver badge. The automobile-driver badge will be hidden in the
     * application, and all people having the automobile-driver badge will be selected when using the car-driver badge.
     *
     * @var BadgeReadFacade|null
     */
    protected $replacedBy;

    /**
     * Number of badges a badge replaces.
     *
     * It's the reverse side of the property above. For example, if automobile-driver is replaced by car-driver, then
     * car-driver will have automobile-driver there. In order to handle pagination and prevent against nesting loops,
     * the list of badges have been put in another API call.
     *
     * @var int
     */
    protected $replacesCount = 0;

    /**
     * Number of people having the given badge.
     *
     * @var int
     */
    protected $peopleCount = 0;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = parent::getExample($decorates);

        $facade->setCategory(
            CategoryFacade::getExample()
        );

        return $facade;
    }

    public function getCategory() : ?CategoryFacade
    {
        return $this->category;
    }

    public function setCategory(?CategoryFacade $category) : BadgeReadFacade
    {
        $this->category = $category;

        return $this;
    }

    public function getCoveredBy() : int
    {
        return $this->coveredBy;
    }

    public function setCoveredBy(int $coveredBy) : BadgeReadFacade
    {
        $this->coveredBy = $coveredBy;

        return $this;
    }

    public function getCoversCount() : int
    {
        return $this->coversCount;
    }

    public function setCoversCount(int $coversCount) : BadgeReadFacade
    {
        $this->coversCount = $coversCount;

        return $this;
    }

    public function getReplacedBy() : ?BadgeFacade
    {
        return $this->replacedBy;
    }

    public function setReplacedBy(?BadgeFacade $replacedBy) : BadgeReadFacade
    {
        $this->replacedBy = $replacedBy;

        return $this;
    }

    public function getReplacesCount() : int
    {
        return $this->replacesCount;
    }

    public function setReplacesCount(int $replacesCount) : BadgeReadFacade
    {
        $this->replacesCount = $replacesCount;

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
}