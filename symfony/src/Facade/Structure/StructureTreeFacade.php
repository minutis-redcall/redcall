<?php

namespace App\Facade\Structure;

use App\Facade\Resource\StructureResourceFacade;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureTreeFacade implements FacadeInterface
{
    /**
     * The ancestors structure list (without children)
     *
     * @var StructureResourceFacade[]
     */
    private $parents = [];

    /**
     * The current structure
     *
     * @var StructureResourceFacade
     */
    private $current;

    /**
     * The children of the current structure, and their possible children
     *
     * @var StructureTreeFacade[]
     */
    private $children = [];

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $current = new StructureResourceFacade();
        $current->setExternalId('889');
        $current->setLabel('Paris 1er');
        $facade->setCurrent($current);

        $parent = new StructureResourceFacade();
        $parent->setExternalId('75');
        $parent->setLabel('DT de Paris');
        $facade->addParent($parent);

        $parentOfParent = new StructureResourceFacade();
        $parentOfParent->setExternalId('1');
        $parentOfParent->setLabel('France Metropolitaine');
        $facade->addParent($parentOfParent);

        foreach (['Quartier Louvre', 'Quartier Etienne Marcel'] as $key => $district) {
            $child = new StructureResourceFacade();
            $child->setExternalId(sprintf('89%d', $key));
            $child->setLabel($district);

            $childTree = new self();
            $childTree->setCurrent($child);
            $childTree->addParent($current);
            $childTree->addParent($parent);
            $childTree->addParent($parentOfParent);

            $facade->addChild($childTree);
        }

        return $facade;
    }

    public function getCurrent() : StructureResourceFacade
    {
        return $this->current;
    }

    public function setCurrent(StructureResourceFacade $current) : StructureTreeFacade
    {
        $this->current = $current;

        return $this;
    }

    public function getParents() : array
    {
        return $this->parents;
    }

    public function addParent(StructureResourceFacade $StructureResourceFacade)
    {
        $this->parents[] = $StructureResourceFacade;

        return $this;
    }

    public function getChildren() : array
    {
        return $this->children;
    }

    public function addChild(self $structureTreeFacade)
    {
        $this->children[] = $structureTreeFacade;

        return $this;
    }
}