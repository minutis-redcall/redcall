<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Manager\PlatformConfigManager;
use App\Manager\UserManager;
use App\Model\Csrf;
use App\Model\PlatformConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ROOT")
 * @Route(path="admin/platform", name="admin_platform_")
 */
class PlatformController extends AbstractController
{
    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(PlatformConfigManager $platformManager, UserManager $userManager)
    {
        $this->platformManager = $platformManager;
        $this->userManager     = $userManager;
    }

    /**
     * @Template
     */
    public function renderSwitch(Request $request)
    {
        return [
            'platforms' => $this->platformManager->getAvailablePlatforms(),
        ];
    }

    /**
     * @Route(name="switch_me", path="/switch-me/{csrf}/{platform}")
     */
    public function switchMe(Csrf $csrf, PlatformConfig $platform)
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setPlatform($platform);

        $this->userManager->save($user);

        return $this->redirectToRoute('home');
    }
}