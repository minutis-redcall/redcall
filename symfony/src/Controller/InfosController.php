<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\VolunteerSession;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/infos/{sessionId}", name="infos_")
 * @IsGranted("VOLUNTEER_SESSION", subject="session")
 */
class InfosController extends BaseController
{
    /**
     * @Route(path="/", name="home")
     */
    public function infos(VolunteerSession $session)
    {
        // todo

        return $this->render('infos.html.twig', [
            'volunteer' => $session->getVolunteer(),
        ]);
    }

}