<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Campaign;
use Symfony\Component\Routing\Annotation\Route;

//{{ campaign.code }}

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

}