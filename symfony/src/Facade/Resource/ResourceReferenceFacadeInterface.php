<?php

namespace App\Facade\Resource;

use Bundles\ApiBundle\Contracts\FacadeInterface;

interface ResourceReferenceFacadeInterface extends FacadeInterface
{
    public function getExternalId() : string;
}