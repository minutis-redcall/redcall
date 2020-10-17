<?php

namespace App\Controller;

use App\Entity\Volunteer;
use App\Manager\LocaleManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @var LocaleManager
     */
    private $locale;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @param LocaleManager           $locale
     * @param VolunteerManager        $volunteerManager
     * @param VolunteerSessionManager $volunteerSessionManager
     */
    public function __construct(LocaleManager $locale,
        VolunteerManager $volunteerManager,
        VolunteerSessionManager $volunteerSessionManager)
    {
        $this->locale                  = $locale;
        $this->volunteerManager        = $volunteerManager;
        $this->volunteerSessionManager = $volunteerSessionManager;
    }

    /**
     * @Route(name="home")
     */
    public function home()
    {
        if (!$this->isGranted('ROLE_TRUSTED')) {
            return $this->redirectToRoute('password_login_not_trusted');
        }

        return $this->render('home.html.twig', [
            'issues' => $this->volunteerManager->findIssues(),
        ]);
    }

    /**
     * @Route("/locale/{locale}", name="locale")
     */
    public function locale(string $locale)
    {
        $this->locale->save($locale);

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/auth", name="auth")
     */
    public function auth()
    {
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/go-to-space", name="go_to_space")
     */
    public function space()
    {
        /** @var Volunteer|null $volunteer */
        $volunteer = $this->getUser()->getVolunteer();
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('space_home', [
            'sessionId' => $this->volunteerSessionManager->createSession($volunteer),
        ]);
    }
}
