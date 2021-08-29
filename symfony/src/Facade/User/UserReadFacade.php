<?php

namespace App\Facade\User;

use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class UserReadFacade extends UserFacade
{
    /**
     * A user is a resource that only helps in the authentication and authorization process. In order to create
     * triggers, a user should be bound to its volunteer, containing all its context at the Red Cross.
     *
     * @var VolunteerResourceFacade
     */
    protected $volunteer = null;

    /**
     * Structures that a user can trigger, by default the same of its tied volunteer.
     *
     * @var StructureResourceFacade[]
     */
    protected $structures = [];

    /**
     * A locked user is not synchronized anymore with its associated volunteer. If volunteer's scope change
     * it won't be reflected.
     *
     * @var bool|null
     */
    protected $locked;

    public static function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $volunteer = new VolunteerResourceFacade();
        $volunteer->setLabel('John Doe');
        $volunteer->setExternalId('demo-volunteer');
        $facade->setVolunteer($volunteer);

        $structureA = new StructureResourceFacade();
        $structureA->setLabel('UNITE LOCALE DE PARIS 5EME');
        $structureA->setExternalId('demo-structure-1');

        $structureB = new StructureResourceFacade();
        $structureB->setLabel('UNITE LOCALE DE PARIS 7EME');
        $structureB->setExternalId('demo-structure-2');

        $facade->setStructures([
            $structureA,
            $structureB,
        ]);

        $facade->setLocked(false);

        return $facade;
    }

    public function getVolunteer() : ?VolunteerResourceFacade
    {
        return $this->volunteer;
    }

    public function setVolunteer(?VolunteerResourceFacade $volunteer) : UserReadFacade
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getStructures() : array
    {
        return $this->structures;
    }

    public function setStructures(array $structures) : UserReadFacade
    {
        $this->structures = $structures;

        return $this;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : UserReadFacade
    {
        $this->locked = $locked;

        return $this;
    }
}