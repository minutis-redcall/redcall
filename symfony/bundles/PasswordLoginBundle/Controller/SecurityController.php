<?php

namespace Bundles\PasswordLoginBundle\Controller;

use Bundles\PasswordLoginBundle\Base\BaseController;
use Bundles\PasswordLoginBundle\Entity\Captcha;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Entity\User;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;

/**
 * @Route(name="password_login_")
 */
class SecurityController extends BaseController
{
    /**
     * @Route("/register", name="register")
     * @Template()
     */
    public function registerAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

        $user = new User();

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
            $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $user->getPassword()));

            $this->dispatch(PasswordLoginEvents::PRE_REGISTER, new PreRegisterEvent($user));

            $this->getManager()->persist($user);
            $this->getManager()->flush($user);

            $this->sendEmailVerification($user->getUsername(), EmailVerification::TYPE_REGISTRATION);

            $this->dispatch(PasswordLoginEvents::POST_REGISTER, new PostRegisterEvent($user));

            $this->success('password_login.register.success');

            return new RedirectResponse(
                $this->generateUrl('home')
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
                $this->generateUrl('home')
            );
        }

        /** @var EmailVerification $emailVerification */
        $emailVerification = $this->getManager(EmailVerification::class)->getUsernameByToken($uuid);
        if (null === $emailVerification) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getManager(User::class)->find($emailVerification->getUsername());
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $this->dispatch(PasswordLoginEvents::PRE_VERIFY_EMAIL, new PreVerifyEmailEvent($user));

        $user->setIsVerified(true);

        if ($emailVerification->getType() == EmailVerification::TYPE_REGISTRATION) {
            $this->sendEmailToAdmins($user->getUsername());
        }

        $this->getManager()->persist($user);
        $this->getManager()->remove($emailVerification);
        $this->getManager()->flush();

        $this->dispatch(PasswordLoginEvents::POST_VERIFY_EMAIL, new PostVerifyEmailEvent($user));

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $this->success('password_login.verify_email.success');

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/connect", name="connect")
     * @Template()
     */
    public function connectAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

        $connectForm = $this
            ->createForm(ConnectType::class)
            ->handleRequest($request);

        if ($this->get('session')->has(Security::AUTHENTICATION_ERROR)) {
            $connectForm->addError(
                new FormError($this->trans('password_login.connect.incorrect'))
            );

            $this->get('session')->remove(Security::AUTHENTICATION_ERROR);
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
        $formUser = new User();
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

            if ($this->getUser()->getUsername() === 'admin@example.com') {
                $this->addFlash('alert', 'Cannot modify the FIC user, create your owns :)');

                return [
                    'profile' => $profileForm->createView(),
                ];
            }

            $newUser = $this->getUser();
            $oldUser = clone $newUser;

            if ($newUser->getUsername() !== $formUser->getUsername()) {
                $newUser->setUsername($formUser->getUsername());
                $newUser->setIsVerified(false);
                $this->sendEmailVerification($newUser->getUsername(), EmailVerification::TYPE_EDIT_PROFILE);
                $this->get('security.token_storage')->setToken(null);
                $this->alert('password_login.profile.logout');
            }

            if ($formUser->getPassword()) {
                $newUser->setPassword(
                    $this->get('security.password_encoder')->encodePassword($newUser, $formUser->getPassword())
                );
            }

            $this->dispatch(PasswordLoginEvents::PRE_EDIT_PROFILE, new PreEditProfileEvent($oldUser, $newUser));

            $this->getManager()->persist($newUser);
            $this->getManager()->flush($newUser);

            $this->dispatch(PasswordLoginEvents::POST_EDIT_PROFILE, new PostEditProfileEvent($newUser, $oldUser));

            $this->success('password_login.profile.success');

            return new RedirectResponse(
                $this->generateUrl('home')
            );
        }

        return [
            'profile' => $profileForm->createView(),
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
            return $this->redirectToRoute('home');
        }

        $forgotPassword = $this
            ->createForm(ForgotPasswordType::class)
            ->handleRequest($request);

        if ($forgotPassword->isSubmitted()) {
            $this->decreaseGrace();
        }

        if ($forgotPassword->isSubmitted() && $forgotPassword->isValid()) {
            $username = $forgotPassword->getData()['username'];

            if ($this->getManager(User::class)->find($username)) {
                $uuid = $this->getManager(PasswordRecovery::class)->generateToken($username);
                $url = trim(getenv('WEBSITE_URL'), '/') . $this->generateUrl('password_login_change_password', ['uuid' => $uuid]);

                $this->get('password_login.mail.service')->send(
                    $username,
                    'password_login.forgot_password.subject',
                    '@PasswordLogin/security/forgot_password_mail.txt.twig',
                    ['url' => $url, 'type' => 'register']
                );
            }

            $this->success('password_login.forgot_password.sent', ['%email%' => $username]);

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
                $this->generateUrl('home')
            );
        }

        $passwordRecovery = $this->getManager(PasswordRecovery::class)->getUsernameByToken($uuid);
        if (null === $passwordRecovery) {
            throw $this->createNotFoundException();
        }

        $user = $this->getManager(User::class)->find($passwordRecovery->getUsername());
        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $changePassword = $this
            ->createForm(ChangePasswordType::class)
            ->handleRequest($request);

        if ($changePassword->isSubmitted() && $changePassword->isValid()) {
            $this->dispatch(PasswordLoginEvents::PRE_CHANGE_PASSWORD, new PreChangePasswordEvent($user));

            $newPassword = $changePassword->getData()['password'];

            $user->setPassword(
                $this->get('security.password_encoder')->encodePassword($user, $newPassword)
            );

            $this->getManager()->persist($user);
            $this->getManager()->remove($passwordRecovery);
            $this->getManager()->flush();

            $this->dispatch(PasswordLoginEvents::POST_CHANGE_PASSWORD, new PostChangePasswordEvent($user));

            $this->success('password_login.change_password.success');

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
        $uuid = $this->getManager(EmailVerification::class)->generateToken($username, $type);
        $url = trim(getenv('WEBSITE_URL'), '/') . $this->generateUrl('password_login_verify_email', ['uuid' => $uuid]);

        $this->get('password_login.mail.service')->send(
            $username,
            'password_login.verify_email.subject',
            '@PasswordLogin/security/verify_email_mail.txt.twig',
            ['url' => $url, 'type' => $type]
        );
    }

    private function sendEmailToAdmins(string $username)
    {
        $admins = $this->getManager(User::class)->findBy([
            'isVerified' => true,
            'isTrusted' => true,
            'isAdmin' => true,
        ]);

        foreach ($admins as $admin) {
            $this->get('password_login.mail.service')->send(
                $admin->getUsername(),
                'password_login.notice_administrators.subject',
                '@PasswordLogin/security/notice_administrators_mail.txt.twig',
                ['username' => $username]
            );
        }
    }

    private function decreaseGrace()
    {
        $this->getManager(Captcha::class)->decreaseGrace(
            $this->get('request_stack')->getMasterRequest()->getClientIp()
        );
    }
}
