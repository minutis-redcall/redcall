<?php

namespace App\Transformer\Admin;

use App\Facade\Pegass\PegassFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\PegassCrawlerBundle\Entity\Pegass;

class PegassTransformer extends BaseTransformer
{
    public function expose($object) : ?FacadeInterface
    {
        /** @var Pegass $pegass */
        $pegass = $object;

        $facade = new PegassFacade();
        $facade->setType($pegass->getType());
        $facade->setIdentifier($pegass->getIdentifier());
        $facade->setParentIdentifier($pegass->getParentIdentifier());
        $facade->setContent($pegass->getContent());
        $facade->setUpdatedAt($pegass->getUpdatedAt());

        return $facade;
    }
}