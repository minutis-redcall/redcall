<?php

namespace Bundles\PasswordLoginBundle\Base;

use Bundles\PasswordLoginBundle\Traits\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    use ServiceTrait;

    public function info($message, array $parameters = [])
    {
        $this->addFlash('info', $this->trans($message, $parameters));
    }

    public function alert($message, array $parameters = [])
    {
        $this->addFlash('alert', $this->trans($message, $parameters));
    }

    public function danger($message, array $parameters = [])
    {
        $this->addFlash('danger', $this->trans($message, $parameters));
    }

    public function success($message, array $parameters = [])
    {
        $this->addFlash('success', $this->trans($message, $parameters));
    }
}