<?php

namespace App\Facade\Structure;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class VolunteerFiltersFacade extends \App\Facade\Volunteer\VolunteerFiltersFacade
{
    /**
     * Whether RedCall should return volunteers belonging to the structure, or
     * volunteers belonging to the structure + all volunteers belonging to any
     * structure in the structure hierarchy tree below the structure.
     *
     * For example:
     * - "DT de Paris" has 144 volunteers
     * - "DT de Paris" and all structures in its scopes have 3200 volunteers
     *
     * Using `include_hierarchy` = false returns the 144 volunteers
     * Using `include_hierarchy` = true returns the 3200 volunteers
     *
     * @var bool
     */
    protected $includeHierarchy = true;

    public static function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $facade->includeHierarchy = true;

        return $facade;
    }

    public function isIncludeHierarchy() : bool
    {
        return $this->includeHierarchy;
    }

    public function setIncludeHierarchy(bool $includeHierarchy) : self
    {
        $this->includeHierarchy = $includeHierarchy;

        return $this;
    }
}