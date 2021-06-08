<?php

namespace App\Facade\Volunteer;

use App\Facade\Badge\BadgeReadFacade;
use App\Facade\Phone\PhoneFacade;
use App\Facade\Structure\StructureFacade;
use App\Facade\User\UserFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;

class VolunteerReadFacade extends VolunteerFacade
{
    /**
     * Structures from which volunteer can be triggered
     *
     * @var StructureFacade[]
     */
    protected $structures;

    /**
     * Volunteer phone numbers
     *
     * @var PhoneFacade[]
     */
    protected $phones;

    /**
     * Volunteer badges
     *
     * @var BadgeReadFacade[]
     */
    protected $badges;

    /**
     * A RedCall user resource if the volunteer can trigger people.
     *
     * @var UserFacade|null
     */
    protected $user;

    public function __construct()
    {
        $this->structures = new CollectionFacade();
        $this->phones     = new CollectionFacade();
        $this->badges     = new CollectionFacade();
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = parent::getExample($decorates);

        // ...

        return $facade;
    }
}