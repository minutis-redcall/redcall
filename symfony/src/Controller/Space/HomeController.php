<?php

namespace App\Controller\Space;

use App\Base\BaseController;
use App\Entity\VolunteerSession;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/space/{sessionId}", name="space_")
 * @IsGranted("VOLUNTEER_SESSION", subject="session")
 */
class HomeController extends BaseController
{
    /**
     * @Route(name="home")
     */
    public function index(VolunteerSession $session)
    {


        // todo

        return $this->render('space/home/index.html.twig', [
            'volunteer' => $session->getVolunteer(),
        ]);
    }

}