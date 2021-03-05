<?php

namespace Bundles\ApiBundle\Base;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Contracts\TransformerInterface;

abstract class BaseTransformer extends BaseService implements TransformerInterface
{
    public static function getSubscribedServices()
    {
        return [];
    }

    public function expose($object) : ?FacadeInterface
    {
        throw new \RuntimeException('Not implemented');
    }

    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        throw new \RuntimeException('Not implemented');
    }
}
