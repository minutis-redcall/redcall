<?php

namespace Bundles\PasswordLoginBundle\Controller;

use Bundles\PasswordLoginBundle\Base\BaseController;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Bundles\PasswordLoginBundle\Form\Type\ProfileType;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Bundles\PasswordLoginBundle\Manager\EmailVerificationManager;
use Bundles\PasswordLoginBundle\Manager\PasswordRecoveryManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Bundles\PasswordLoginBundle\Services\Mail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/admin/users", name="password_login_admin_")
 */
class AdminController extends BaseController
{
    /**
     * @var CaptchaManager
     */
    private $captchaManager;

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
     * @var Mail
     */
    private $mail;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $homeRoute;

    public function __construct(CaptchaManager $captchaManager,
        EmailVerificationManager $emailVerificationManager,
        PasswordRecoveryManager $passwordRecoveryManager,
        UserManager $userManager,
        EventDispatcherInterface $dispatcher,
        Mail $mail,
        UserPasswordEncoderInterface $encoder,
        TokenStorageInterface $tokenStorage,
        Session $session,
        string $homeRoute)
    {
        $this->captchaManager           = $captchaManager;
        $this->emailVerificationManager = $emailVerificationManager;
        $this->passwordRecoveryManager  = $passwordRecoveryManager;
        $this->userManager              = $userManager;
        $this->dispatcher               = $dispatcher;
        $this->mail                     = $mail;
        $this->encoder                  = $encoder;
        $this->tokenStorage             = $tokenStorage;
        $this->session                  = $session;
        $this->homeRoute                = $homeRoute;
    }

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

        return [
            'search'    => $search->createView(),
            'users'     => $this->userManager->searchAll($criteria),
            'homeRoute' => $this->homeRoute,
        ];
    }

    /**
     * @Route("/toggle-verify/{username}/{csrf}", name="toggle_verify")
     */
    public function toggleVerify($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        $user->setIsVerified(1 - $user->isVerified());
        $this->userManager->save($user);

        $emailVerification = $this->emailVerificationManager->find($username);
        if ($emailVerification) {
            $this->emailVerificationManager->remove($emailVerification);
        }

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/toggle-trust/{username}/{csrf}", name="toggle_trust")
     */
    public function toggleTrust($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        $user->setIsTrusted(1 - $user->isTrusted());
        $this->userManager->save($user);

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/toggle-admin/{username}/{csrf}", name="toggle_admin")
     */
    public function toggleAdmin($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        $user->setIsAdmin(1 - $user->isAdmin());
        $this->userManager->save($user);

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/delete/{username}/{csrf}", name="delete")
     */
    public function delete($username, $csrf)
    {
        $user = $this->checkCsrfAndUser($username, $csrf);

        $this->userManager->remove($user);

        if ($passwordRecovery = $this->passwordRecoveryManager->find($username)) {
            $this->passwordRecoveryManager->remove($passwordRecovery);
        }

        if ($emailVerification = $this->emailVerificationManager->find($username)) {
            $this->emailVerificationManager->remove($emailVerification);
        }

        return $this->redirectToRoute('password_login_admin_list');
    }

    /**
     * @Route("/profile/{username}", name="profile")
     * @Template()
     */
    public function profile(Request $request, string $username)
    {
        $newUser = $this->checkUser($username);
        $oldUser = clone $newUser;

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

            $this->success('password_login.profile.saved');

            return $this->redirectToRoute('password_login_admin_profile', [
                'username' => $newUser->getUsername(),
            ]);
        }

        return [
            'user' => $newUser,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/reset-password/{username}/{csrf}", name="reset_password")
     */
    public function resetPassword($username, $csrf)
    {
        if ($this->checkCsrfAndUser($username, $csrf)) {
            $uuid = $this->passwordRecoveryManager->generateToken($username);
            $url  = trim(getenv('WEBSITE_URL'), '/').$this->generateUrl('password_login_change_password', ['uuid' => $uuid]);

            $this->mail->send(
                $username,
                'password_login.forgot_password.subject',
                '@PasswordLogin/security/forgot_password_mail.txt.twig',
                ['url' => $url, 'type' => 'register']
            );
        }

        $this->success('password_login.forgot_password.sent_by_admin', ['%email%' => $username]);

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