<?php

namespace App\Facade\Resource;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;

interface ResourceReferenceCollectionFacadeInterface extends FacadeInterface
{
    public function getEntries() : CollectionFacade;
}
