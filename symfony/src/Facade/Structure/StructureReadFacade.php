<?php

namespace App\Facade\Structure;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureReadFacade extends StructureFacade
{
    /**
     * Number of volunteers that can be triggered in the given structure.
     *
     * These people will receive messages when the structure will be selected.
     *
     * @var int
     */
    protected $volunteersCount = 0;

    /**
     * Number of volunteers that can trigger in the given structure.
     *
     * These RedCall users can select this structure in order to send messages
     * to its volunteers.
     *
     * @var int
     */
    protected $usersCount = 0;

    /**
     * The parent structure's external id.
     *
     * @var string
     */
    protected $parentStructureExternalId;

    /**
     * Chlidren structures' external ids.
     *
     * @var string[]
     */
    protected $childrenStructureExternalIds = [];

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = parent::getExample($decorates);

        // ...

        return $facade;
    }
}