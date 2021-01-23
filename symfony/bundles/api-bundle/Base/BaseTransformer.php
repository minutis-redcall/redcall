<?php

namespace Bundles\ApiBundle\Base;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Contracts\TransformerInterface;

class BaseTransformer implements TransformerInterface
{
    public function expose($object) : FacadeInterface
    {
        throw new \RuntimeException('Not implemented');
    }

    public function reconstruct(FacadeInterface $facade)
    {
        throw new \RuntimeException('Not implemented');
    }
}
