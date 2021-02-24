<?php

namespace Bundles\ApiBundle\Contracts;

interface TransformerInterface
{
    public function expose($object) : FacadeInterface;

    public function reconstruct(FacadeInterface $facade, $object = null);
}
