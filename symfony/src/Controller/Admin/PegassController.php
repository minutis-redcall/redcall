<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use Bundles\PasswordLoginBundle\Entity\User;
use Bundles\PasswordLoginBundle\Manager\UserManager;

class PegassController extends BaseController
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function index()
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/pegass/index.html.twig', [
            'users' => $this->getManager(User::class)->findAll(),
        ]);
    }

}