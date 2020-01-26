<?php

namespace App\Controller;

use App\Manager\LocaleManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomeController extends Controller
{
    private $locale;

    /**
     * HomeController constructor.
     *
     * @param LocaleManager $locale
     */
    public function __construct(LocaleManager $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @Route(name="home")
     */
    public function indexAction()
    {
        if (!$this->isGranted('ROLE_TRUSTED')) {
            return $this->redirectToRoute('password_login_not_trusted');
        }

        return $this->render('home.html.twig');
    }

    /**
     * @Route("/locale/{locale}", name="locale")
     */
    public function localeAction(string $locale)
    {
        $this->locale->save($locale);

        return $this->redirectToRoute('home');
    }
}
