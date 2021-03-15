<?php

namespace App\Transformer\Admin;

use App\Entity\Category;
use App\Facade\Admin\Category\CategoryFacade;
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

    public function expose($object) : ?FacadeInterface
    {
        /** @var Category $category */
        $category = $object;

        if (!$category) {
            return null;
        }

        $facade = new CategoryFacade();
        $facade->setExternalId($category->getExternalId());
        $facade->setName($category->getName());
        $facade->setPriority($category->getPriority());

        return $facade;
    }

    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        $category = $object;
        if (!$category) {
            $category = new Category();
            $category->setPlatform($this->security->getPlatform());
        }

        /** @var CategoryFacade $facade */
        if ($facade->getExternalId()) {
            $category->setExternalId($facade->getExternalId());
        }

        if ($facade->getName()) {
            $category->setName($facade->getName());
        }

        if ($facade->getPriority()) {
            $category->setPriority($facade->getPriority());
        }

        return $category;
    }
}