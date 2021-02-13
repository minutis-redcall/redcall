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

    public function getCategory(string $className) : ?CategoryDescription
    {
        return $this->categories[$className] ?? $this->categories[sha1($className)] ?? null;
    }

    public function add(CategoryDescription $category) : self
    {
        $this->categories[$category->getId()] = $category;

        return $this;
    }

    public function sort()
    {
        foreach ($this->categories as $category) {
            $category->getEndpoints()->sort();
        }

        uasort($this->categories, function (CategoryDescription $a, CategoryDescription $b) {
            return $a->getEndpoints()->getPriority() <=> $b->getEndpoints()->getPriority();
        });
    }
}