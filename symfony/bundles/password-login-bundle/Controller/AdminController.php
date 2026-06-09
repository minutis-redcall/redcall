<?php

namespace Bundles\PasswordLoginBundle\Controller;

use App\Entity\User;
use App\Manager\UserAuditLogManager;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Bundles\PasswordLoginBundle\Form\Type\ProfileType;
use Bundles\PasswordLoginBundle\Manager\EmailVerificationManager;
use Bundles\PasswordLoginBundle\Manager\PasswordRecoveryManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/admin/users", name: "password_login_admin_")]
class AdminController extends AbstractController
{
    /**
     * @var EmailVerificationManager
     */
    private $emailVerificationManager;

    /**
     * @var PasswordRecoveryManager
     */
    private $passwordRecoveryManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserAuditLogManager
     */
    private $userAuditLogManager;

    public function __construct(EmailVerificationManager $emailVerificationManager,
        PasswordRecoveryManager $passwordRecoveryManager,
        UserManager $userManager,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        UserAuditLogManager $userAuditLogManager)
    {
        $this->emailVerificationManager = $emailVerificationManager;
        $this->passwordRecoveryManager  = $passwordRecoveryManager;
        $this->userManager              = $userManager;
        $this->dispatcher               = $dispatcher;
        $this->translator               = $translator;
        $this->userAuditLogManager      = $userAuditLogManager;
    }

    /**
     * RedCall has its own admin UI under /admin/redcall-users which fully
     * replaces the generic list shipped with this bundle. The old route
     * name is kept as a redirect so any bookmark, link in PasswordLogin's
     * shared `menu.html.twig`, or external doc still lands on a working
     * page.
     */
    #[Route("/", name: "list")]
    public function listAction()
    {
        return $this->redirectToRoute('admin_redcall_users_index');
    }

    #[Route("/profile/{username}", name: "profile")]
    #[Template("@PasswordLogin/admin/profile.html.twig")]
    public function profile(Request $request, string $username)
    {
        $newUser = $this->checkUser($username);
        $oldUser = clone $newUser;
        $oldSnapshot = $newUser instanceof User ? $this->userAuditLogManager->buildSnapshot($newUser) : null;

        $form = $this
            ->createForm(ProfileType::class, $newUser, [
                'admin' => true,
                'user'  => $newUser,
            ])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatcher->dispatch(new PreEditProfileEvent($oldUser, $newUser), PasswordLoginEvents::PRE_EDIT_PROFILE);

            $this->userManager->save($newUser);

            $this->dispatcher->dispatch(new PostEditProfileEvent($newUser, $oldUser), PasswordLoginEvents::POST_EDIT_PROFILE);

            if ($newUser instanceof User && null !== $oldSnapshot) {
                $actor = $this->getUser() instanceof User ? $this->getUser() : null;
                $this->userAuditLogManager->logUpdated($actor, null, $newUser, $oldSnapshot);
            }

            $this->addFlash('success', $this->translator->trans('password_login.profile.saved'));

            return $this->redirectToRoute('password_login_admin_profile', [
                'username' => $newUser->getUsername(),
            ]);
        }

        return [
            'user' => $newUser,
            'form' => $form->createView(),
        ];
    }

    #[Route("/reset-password/{username}/{csrf}", name: "reset_password")]
    public function resetPassword($username, $csrf)
    {
        if ($this->checkCsrfAndUser($username, $csrf)) {
            $this->passwordRecoveryManager->sendPasswordRecoveryEmail($username);
        }

        $this->addFlash('success', $this->translator->trans('password_login.forgot_password.sent_by_admin', ['%email%' => $username]));

        return $this->redirectToRoute('password_login_admin_profile', [
            'username' => $username,
        ]);
    }

    private function checkCsrfAndUser($username, $csrf) : AbstractUser
    {
        if (!$this->isCsrfTokenValid('password_login', $csrf)) {
            throw $this->createNotFoundException();
        }

        return $this->checkUser($username);
    }

    private function checkUser(string $username) : AbstractUser
    {
        $user = $this->userManager->findOneByUsername($username);
        if (is_null($user)) {
            throw $this->createNotFoundException();
        }

        if ($user->isEqualTo($this->getUser())) {
            throw $this->createNotFoundException();
        }

        return $user;
    }

}