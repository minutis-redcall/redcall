<?php

namespace App\Controller;

use App\Base\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="_ah")
 */
class GoogleController extends BaseController
{
    /**
     * @Route("/start")
     */
    public function start()
    {
        return new Response();
    }

    /**
     * @Route("/stop")
     */
    public function stop()
    {
        return new Response();
    }
}