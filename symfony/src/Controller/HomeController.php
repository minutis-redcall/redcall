<?php

namespace App\Controller;

use App\Entity\Volunteer;
use App\Manager\LocaleManager;
use App\Manager\VolunteerSessionManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route(name="home")
     */
    public function home()
    {
        if (!$this->isGranted('ROLE_TRUSTED')) {
            return $this->redirectToRoute('password_login_not_trusted');
        }

        return $this->render('home.html.twig');
    }

    /**
     * @Route("/locale/{locale}", name="locale")
     */
    public function locale(LocaleManager $localeManager, string $locale)
    {
        $localeManager->save($locale);

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
    public function space(VolunteerSessionManager $volunteerSessionManager)
    {
        /** @var Volunteer|null $volunteer */
        $volunteer = $this->getUser()->getVolunteer();
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('space_home', [
            'sessionId' => $volunteerSessionManager->createSession($volunteer),
        ]);
    }
}
