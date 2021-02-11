<?php

namespace Bundles\ApiBundle\Model\Documentation;

class CategoryCollectionDescription
{
    /**
     * @var CategoryDescription[]
     */
    private $categories;

    public function getCategories() : array
    {
        return $this->categories;
    }

    public function add(CategoryDescription $category) : self
    {
        $this->categories[] = $category;

        return $this;
    }

    public function sort()
    {
        foreach ($this->categories as $category) {
            $category->getEndpoints()->sort();
        }

        usort($this->categories, function (CategoryDescription $a, CategoryDescription $b) {
            return $a->getEndpoints()->getPriority() <=> $b->getEndpoints()->getPriority();
        });
    }
}