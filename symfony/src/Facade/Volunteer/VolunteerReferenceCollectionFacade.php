<?php

namespace App\Facade\Volunteer;

use App\Facade\Resource\ResourceCollectionFacadeInterface;
use App\Facade\Resource\ResourceReferenceCollectionFacadeInterface;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Symfony\Component\Validator\Constraints as Assert;

class VolunteerReferenceCollectionFacade implements ResourceReferenceCollectionFacadeInterface
{
    /**
     * Contains an array of volunteer external ids.
     *
     * @Assert\NotNull
     * @Assert\Count(min=1, max=100)
     *
     * @var VolunteerReferenceFacade[]
     */
    protected $entries;

    public function __construct()
    {
        $this->entries = new CollectionFacade();
    }

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->setEntries([
            VolunteerReferenceFacade::getExample(),
            VolunteerReferenceFacade::getExample(),
            VolunteerReferenceFacade::getExample(),
        ]);

        return $facade;
    }

    public function getEntries() : CollectionFacade
    {
        return $this->entries;
    }

    public function setEntries(array $entries)
    {
        $this->entries = new CollectionFacade($entries);
    }
}