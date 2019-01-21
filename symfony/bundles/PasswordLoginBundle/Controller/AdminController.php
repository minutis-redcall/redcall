<?php

namespace Bundles\PasswordLoginBundle\Controller;

use Bundles\PasswordLoginBundle\Base\BaseController;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/admin/users", name="password_login_admin_")
 */
class AdminController extends BaseController
{
    /**
     * @Route("/", name="list")
     * @Template()
     */
    public function listAction()
    {
        return [
            'users' => $this->getManager(User::class)->findAll(),
        ];
    }

    /**
     * @Route("/toggle-verify/{username}/{csrf}", name="toggle_verify")
     */
    public function toggleVerifyAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        if ($user->getUsername() === 'admin@example.com') {
            $this->addFlash('alert', 'Cannot modify the FIC admin user, create your owns :)');

            return $this->redirectToRoute('password_login_admin_list');
        }

        $user->setIsVerified(1 - $user->isVerified());
        $this->getManager()->persist($user);
        $this->getManager()->flush($user);

        $emailVerification = $this->getManager(EmailVerification::class)->find($username);
        if ($emailVerification) {
            $this->getManager()->remove($emailVerification);
            $this->getManager()->flush($emailVerification);
        }

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/toggle-trusted/{username}/{csrf}", name="toggle_trusted")
     */
    public function toggleTrustedAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        if ($user->getUsername() === 'admin@example.com') {
            $this->addFlash('alert', 'Cannot modify the FIC admin user, create your owns :)');

            return $this->redirectToRoute('password_login_admin_list');
        }

        $user->setIsTrusted(1 - $user->isTrusted());
        $this->getManager()->persist($user);
        $this->getManager()->flush($user);

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/toggle-admin/{username}/{csrf}", name="toggle_admin")
     */
    public function toggleAdminAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        if ($user->getUsername() === 'admin@example.com') {
            $this->addFlash('alert', 'Cannot modify the FIC admin user, create your owns :)');

            return $this->redirectToRoute('password_login_admin_list');
        }

        $user->setIsAdmin(1 - $user->isAdmin());
        $this->getManager()->persist($user);
        $this->getManager()->flush($user);

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/delete/{username}/{csrf}", name="delete")
     */
    public function deleteAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        if ($user->getUsername() === 'admin@example.com') {
            $this->addFlash('alert', 'Cannot remove the FIC admin user, create your owns :)');

            return $this->redirectToRoute('password_login_admin_list');
        }

        $this->getManager()->remove($user);

        if ($passwordRecovery = $this->getManager(PasswordRecovery::class)->find($username)) {
            $this->getManager()->remove($passwordRecovery);
        }

        if ($emailVerification = $this->getManager(EmailVerification::class)->find($username)) {
            $this->getManager()->remove($emailVerification);
        }

        $this->getManager()->flush();

        return $this->redirectToRoute('password_login_admin_list');
    }

    protected function checkCsrfAndUser($username, $csrf): User
    {
        if (!$this->isCsrfTokenValid('password_login', $csrf)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getManager(User::class)->find($username);
        if (is_null($user)) {
            throw $this->createNotFoundException();
        }

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        return $user;
    }
}