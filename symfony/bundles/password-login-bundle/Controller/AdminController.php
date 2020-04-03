<?php

namespace Bundles\PasswordLoginBundle\Controller;

use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PaginationBundle\PaginationBundle;
use Bundles\PasswordLoginBundle\Base\BaseController;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/users", name="password_login_admin_")
 */
class AdminController extends BaseController
{
    /**
     * @Route("/", name="list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $search = $this->createSearchForm($request);

        $criteria = null;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        if (class_exists(PaginationBundle::class)) {
            return [
                'pager'  => true,
                'search' => $search->createView(),
                'users'  => $this->get(PaginationManager::class)->getPager(
                    $this->getManager(User::class)->searchAllQueryBuilder($criteria)
                ),
            ];
        }

        return [
            'pager'  => false,
            'search' => $search->createView(),
            'users'  => $this->getManager(User::class)->searchAll($criteria),
        ];
    }

    /**
     * @Route("/toggle-verify/{username}/{csrf}", name="toggle_verify")
     */
    public function toggleVerifyAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

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
     * @Route("/toggle-trust/{username}/{csrf}", name="toggle_trust")
     */
    public function toggleTrustAction($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

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

    private function checkCsrfAndUser($username, $csrf): User
    {
        if (!$this->isCsrfTokenValid('password_login', $csrf)) {
            throw $this->createNotFoundException();
        }

        $user = $this->getManager(User::class)->findOneByUsername($username);
        if (is_null($user)) {
            throw $this->createNotFoundException();
        }

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        return $user;
    }

    private function createSearchForm(Request $request)
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => 'password_login.user_list.search.criteria',
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'password_login.user_list.search.submit',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}