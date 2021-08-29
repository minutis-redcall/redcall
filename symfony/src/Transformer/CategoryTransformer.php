<?php

namespace App\Transformer;

use App\Entity\Category;
use App\Facade\Category\CategoryFacade;
use App\Facade\Category\CategoryReadFacade;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CategoryTransformer extends BaseTransformer
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param Category $object
     *
     * @return CategoryFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new CategoryReadFacade();
        $facade->setExternalId($object->getExternalId());
        $facade->setName($object->getName());
        $facade->setPriority($object->getPriority());
        $facade->setLocked($object->isLocked());
        $facade->setEnabled($object->isEnabled());

        return $facade;
    }

    /**
     * @param CategoryFacade $facade
     * @param Category|null  $object
     *
     * @return Category
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        if (!$object) {
            $object = new Category();
            $object->setPlatform($this->security->getPlatform());
        }

        if (null !== $facade->getExternalId()) {
            $object->setExternalId($facade->getExternalId());
        }

        if (null !== $facade->getName()) {
            $object->setName($facade->getName());
        }

        if (null !== $facade->getPriority()) {
            $object->setPriority($facade->getPriority());
        }

        return $object;
    }
}