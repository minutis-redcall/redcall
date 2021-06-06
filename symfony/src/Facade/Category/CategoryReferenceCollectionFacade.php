<?php

namespace App\Facade\Category;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryReferenceCollectionFacade implements FacadeInterface
{
    /**
     * Contains an array of external ids.
     *
     * @Assert\NotNull
     * @Assert\Count(min=1, max=100)
     *
     * @var CategoryReferenceFacade[]
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
            CategoryReferenceFacade::getExample(),
            CategoryReferenceFacade::getExample(),
            CategoryReferenceFacade::getExample(),
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