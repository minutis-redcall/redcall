<?php

namespace App\Controller\Space;

use App\Base\BaseController;
use App\Entity\VolunteerSession;
use App\Manager\VolunteerSessionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/space/{sessionId}", name="space_")
 * @IsGranted("VOLUNTEER_SESSION", subject="session")
 */
class HomeController extends BaseController
{
    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @param VolunteerSessionManager $volunteerSessionManager
     */
    public function __construct(VolunteerSessionManager $volunteerSessionManager)
    {
        $this->volunteerSessionManager = $volunteerSessionManager;
    }

    /**
     * @Route(name="home")
     */
    public function index(VolunteerSession $session)
    {
        // todo add at the top of all pages the name of the volunteer (just like on /msg)


        // todo

        return $this->render('space/home/index.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * @Route(path="/infos", name="infos")
     */
    public function infos(VolunteerSession $session)
    {
        return $this->render('space/home/infos.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * @Route(path="/logout", name="logout")
     */
    public function logout(VolunteerSession $session)
    {
        $this->volunteerSessionManager->removeSession($session);

        return $this->redirectToRoute('home');
    }
}