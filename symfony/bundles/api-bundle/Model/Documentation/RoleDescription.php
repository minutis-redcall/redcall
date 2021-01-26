<?php

namespace Bundles\ApiBundle\Model\Documentation;

class RoleDescription
{
    /**
     * @var string
     */
    private $attribute;

    /**
     * @var string
     */
    private $object;

    public function getAttribute() : string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute) : RoleDescription
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getObject() : string
    {
        return $this->object;
    }

    public function setObject(string $object) : RoleDescription
    {
        $this->object = $object;

        return $this;
    }
}