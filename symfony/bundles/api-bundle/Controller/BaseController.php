<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\PaginationBundle\Manager\PaginationManager;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseController extends AbstractController
{
    public function getPager($data, $prefix = '', $hasJoins = false) : Pagerfanta
    {
        return $this->get(PaginationManager::class)->getPager($data, $prefix, $hasJoins);
    }
}