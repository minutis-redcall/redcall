<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WARNING: this controller is OUT of the security firewall.
 *
 * @Route(path="syn/{code}", name="synthesis_")
 */
class SynthesisController extends BaseController
{
    /**
     * @Route(name="index")
     */
    public function index(Campaign $campaign)
    {
        return $this->render('synthesis/index.html.twig', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * @Route(path="/poll", name="poll")
     */
    public function poll(Campaign $campaign)
    {
        return $this->render('synthesis/communications.html.twig', [
            'campaign' => $campaign,
        ]);
    }
}