<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Manager\UserInformationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(path="management/", name="management_")
 */
class HomeController extends BaseController
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(UserInformationManager $userInformationManager)
    {
        $this->userInformationManager = $userInformationManager;
    }

    /**
     * @Route(name="home")
     */
    public function indexAction()
    {
        return $this->render('management/home.html.twig', [
            'email' => getenv('MINUTIS_SUPPORT'),
            'user' => $this->userInformationManager->findForCurrentUser(),
        ]);
    }
}
