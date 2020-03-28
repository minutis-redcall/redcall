<?php

namespace App\Controller;

use App\Base\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * GCP calls those routes when service is stopped or
 * started.
 *
 * We'll need to add a "paused" status to communication
 * entity, in order to "pause" the communication when
 * "stop" is reached, and restart it when "start" is
 * called.
 *
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