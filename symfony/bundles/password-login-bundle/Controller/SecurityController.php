<?php

namespace Bundles\PasswordLoginBundle\Controller;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostChangePasswordEvent;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PostRegisterEvent;
use Bundles\PasswordLoginBundle\Event\PostVerifyEmailEvent;
use Bundles\PasswordLoginBundle\Event\PreChangePasswordEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PreRegisterEvent;
use Bundles\PasswordLoginBundle\Event\PreVerifyEmailEvent;
use Bundles\PasswordLoginBundle\Form\Type\ChangePasswordType;
use Bundles\PasswordLoginBundle\Form\Type\ConnectType;
use Bundles\PasswordLoginBundle\Form\Type\ForgotPasswordType;
use Bundles\PasswordLoginBundle\Form\Type\ProfileType;
use Bundles\PasswordLoginBundle\Form\Type\RegistrationType;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Bundles\PasswordLoginBundle\Manager\EmailVerificationManager;
use Bundles\PasswordLoginBundle\Manager\PasswordRecoveryManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Bundles\PasswordLoginBundle\Services\Mail;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(name="password_login_")
 */
class SecurityController extends AbstractController
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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $userClass;

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
        TranslatorInterface $translator,
        RequestStack $requestStack,
        string $userClass,
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
        $this->translator               = $translator;
        $this->requestStack             = $requestStack;
        $this->userClass                = $userClass;
        $this->homeRoute                = $homeRoute;
    }

    /**
     * @Route("/register", name="register")
     * @Template()
     */
    public function registerAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute($this->homeRoute);
        }

        /** @var AbstractUser $user */
        $user = new $this->userClass();

        $registrationForm = $this
            ->createForm(RegistrationType::class, $user)
            ->handleRequest($request);

        if ($registrationForm->isSubmitted() && !$registrationForm->isValid()) {
            $this->decreaseGrace();

            $registrationForm = $this
                ->createForm(RegistrationType::class, $user)
                ->handleRequest($request);
        }

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $user->setPassword($this->encoder->encodePassword($user, $user->getPassword()));

            $this->dispatcher->dispatch(new PreRegisterEvent($user), PasswordLoginEvents::PRE_REGISTER);

            $this->userManager->save($user);

            $this->sendEmailVerification($user->getUsername(), EmailVerification::TYPE_REGISTRATION);

            $this->dispatcher->dispatch(new PostRegisterEvent($user), PasswordLoginEvents::POST_REGISTER);

            $this->addFlash('success', $this->translator->trans('password_login.register.success'));

            return new RedirectResponse(
                $this->generateUrl($this->homeRoute)
            );
        }

        return [
            'registration' => $registrationForm->createView(),
        ];
    }

    /**
     * @Route("/verify-email/{uuid}", name="verify_email")
     */
    public function verifyEmailAction(Request $request, $uuid)
    {
        if ($this->isGranted('ROLE_USER')) {
            return new RedirectResponse(
                $this->generateUrl($this->homeRoute)
            );
        }

        /** @var EmailVerification $emailVerification */
        $emailVerification = $this->emailVerificationManager->getByToken($uuid);
        if (null === $emailVerification) {
            throw $this->createNotFoundException();
        }

        /** @var AbstractUser $user */
        $user = $this->userManager->findOneByUsername($emailVerification->getUsername());
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $this->dispatcher->dispatch(new PreVerifyEmailEvent($user), PasswordLoginEvents::PRE_VERIFY_EMAIL);

        $user->setIsVerified(true);

        if ($emailVerification->getType() == EmailVerification::TYPE_REGISTRATION) {
            $this->sendEmailToAdmins($user->getUsername());
        }

        $this->userManager->save($user);

        $this->dispatcher->dispatch(new PostVerifyEmailEvent($user), PasswordLoginEvents::POST_VERIFY_EMAIL);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        $this->addFlash('success', $this->translator->trans('password_login.verify_email.success'));

        $this->emailVerificationManager->remove($emailVerification);

        return $this->redirectToRoute($this->homeRoute);
    }

    /**
     * @Route("/connect", name="connect")
     * @Template()
     */
    public function connectAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute($this->homeRoute);
        }

        $connectForm = $this
            ->createForm(ConnectType::class)
            ->handleRequest($request);

        if ($this->session->has(Security::AUTHENTICATION_ERROR)) {
            $connectForm->addError(
                new FormError($this->translator->trans('password_login.connect.incorrect'))
            );

            $this->session->remove(Security::AUTHENTICATION_ERROR);
        }

        return [
            'connect' => $connectForm->createView(),
        ];
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        // never reached
    }

    /**
     * @Route("/profile", name="profile")
     * @Template()
     */
    public function profileAction(Request $request)
    {
        $formUser = new AbstractUser();
        $formUser->setUsername($this->getUser()->getUsername());

        $profileForm = $this
            ->createForm(ProfileType::class, $formUser)
            ->handleRequest($request);

        if ($profileForm->isSubmitted() && !$profileForm->isValid()) {
            $this->decreaseGrace();

            $profileForm = $this
                ->createForm(ProfileType::class, $formUser)
                ->handleRequest($request);
        }

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $newUser = $this->getUser();
            $oldUser = clone $newUser;

            if ($newUser->getUsername() !== $formUser->getUsername()) {
                $newUser->setUsername($formUser->getUsername());
                $newUser->setIsVerified(false);
                $this->sendEmailVerification($newUser->getUsername(), EmailVerification::TYPE_EDIT_PROFILE);
                $this->tokenStorage->setToken();
                $this->addFlash('alert', $this->translator->trans('password_login.profile.logout'));
            }

            if ($formUser->getPassword()) {
                $newUser->setPassword(
                    $this->get('security.password_encoder')->encodePassword($newUser, $formUser->getPassword())
                );
            }

            $this->dispatcher->dispatch(new PreEditProfileEvent($oldUser, $newUser), PasswordLoginEvents::PRE_EDIT_PROFILE);

            $this->userManager->save($newUser);

            $this->dispatcher->dispatch(new PostEditProfileEvent($newUser, $oldUser), PasswordLoginEvents::POST_EDIT_PROFILE);

            $this->addFlash('success', $this->translator->trans('password_login.profile.success'));

            return new RedirectResponse(
                $this->generateUrl($this->homeRoute)
            );
        }

        return [
            'profile'   => $profileForm->createView(),
            'homeRoute' => $this->homeRoute,
        ];
    }

    /**
     * @Route("/guest", name="not_trusted")
     * @Template()
     */
    public function notTrustedAction()
    {
        return [];
    }

    /**
     * @Route("/forgot-password", name="forgot_password")
     * @Template()
     */
    public function forgotPasswordAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute($this->homeRoute);
        }

        $forgotPassword = $this
            ->createForm(ForgotPasswordType::class)
            ->handleRequest($request);

        if ($forgotPassword->isSubmitted()) {
            $this->decreaseGrace();
        }

        if ($forgotPassword->isSubmitted() && $forgotPassword->isValid()) {
            $username = $forgotPassword->getData()['username'];

            if ($this->userManager->findOneByUsername($username)) {
                $this->passwordRecoveryManager->sendPasswordRecoveryEmail($username);
            }

            $this->addFlash('success', $this->translator->trans('password_login.forgot_password.sent', ['%email%' => $username]));

            return new RedirectResponse(
                $this->generateUrl('password_login_connect')
            );
        }

        return [
            'forgotPassword' => $forgotPassword->createView(),
        ];
    }

    /**
     * @Route("/change-password/{uuid}", name="change_password")
     * @Template()
     */
    public function changePasswordAction(Request $request, $uuid)
    {
        if ($this->isGranted('ROLE_USER')) {
            return new RedirectResponse(
                $this->generateUrl($this->homeRoute)
            );
        }

        $passwordRecovery = $this->passwordRecoveryManager->getByToken($uuid);
        if (null === $passwordRecovery) {
            throw $this->createNotFoundException();
        }

        $user = $this->userManager->findOneByUsername($passwordRecovery->getUsername());
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $changePassword = $this
            ->createForm(ChangePasswordType::class)
            ->handleRequest($request);

        if ($changePassword->isSubmitted() && $changePassword->isValid()) {
            $this->dispatcher->dispatch(new PreChangePasswordEvent($user), PasswordLoginEvents::PRE_CHANGE_PASSWORD);

            $newPassword = $changePassword->getData()['password'];

            $user->setPassword(
                $this->encoder->encodePassword($user, $newPassword)
            );

            $this->userManager->save($user);

            $this->dispatcher->dispatch(new PostChangePasswordEvent($user), PasswordLoginEvents::POST_CHANGE_PASSWORD);

            $this->addFlash('success', $this->translator->trans('password_login.change_password.success'));

            $this->passwordRecoveryManager->remove($passwordRecovery);

            return new RedirectResponse(
                $this->generateUrl('password_login_connect')
            );
        }

        return [
            'changePassword' => $changePassword->createView(),
        ];
    }

    private function sendEmailVerification(string $username, string $type)
    {
        $uuid = $this->emailVerificationManager->generateToken($username, $type);
        $url  = trim(getenv('WEBSITE_URL'), '/').$this->generateUrl('password_login_verify_email', ['uuid' => $uuid]);

        $this->mail->send(
            $username,
            'password_login.verify_email.subject',
            '@PasswordLogin/security/verify_email_mail.txt.twig',
            ['url' => $url, 'type' => $type]
        );
    }

    private function sendEmailToAdmins(string $username)
    {
        $admins = $this->userManager->findAdmins();

        foreach ($admins as $admin) {
            $this->mail->send(
                $admin->getUsername(),
                'password_login.notice_administrators.subject',
                '@PasswordLogin/security/notice_administrators_mail.txt.twig',
                ['username' => $username]
            );
        }
    }

    private function decreaseGrace()
    {
        $this->captchaManager->decreaseGrace(
            $this->requestStack->getMasterRequest()->getClientIp()
        );
    }
}
