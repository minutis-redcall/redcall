<?php

namespace App\Transformer;

use App\Facade\Pegass\PegassFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use App\Entity\Pegass;

class PegassTransformer extends BaseTransformer
{
    /**
     * @param Pegass $object
     *
     * @return PegassFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new PegassFacade();
        $facade->setType($object->getType());
        $facade->setIdentifier($object->getIdentifier());
        $facade->setParentIdentifier($object->getParentIdentifier());
        $facade->setContent($object->getContent());
        $facade->setUpdatedAt($object->getUpdatedAt());

        return $facade;
    }
}