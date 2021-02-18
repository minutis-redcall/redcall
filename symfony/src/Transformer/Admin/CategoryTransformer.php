<?php

namespace App\Transformer\Admin;

use App\Entity\Category;
use App\Facade\Admin\Category\CategoryFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CategoryTransformer extends BaseTransformer
{
    public function expose($object) : FacadeInterface
    {
        /** @var Category $category */
        $category = $object;

        $facade = new CategoryFacade();
        $facade->setExternalId($category->getExternalId());
        $facade->setName($category->getName());
        $facade->setPriority($category->getPriority());

        return $facade;
    }
}