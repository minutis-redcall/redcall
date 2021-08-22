<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Repository\PasswordRecoveryRepository;
use Bundles\PasswordLoginBundle\Services\Mail;
use Symfony\Component\Routing\RouterInterface;

class PasswordRecoveryManager
{
    /**
     * @var PasswordRecoveryRepository
     */
    private $passwordRecoveryRepository;

    /**
     * @var Mail
     */
    private $mail;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(PasswordRecoveryRepository $passwordRecoveryRepository,
        Mail $mail,
        RouterInterface $router)
    {
        $this->passwordRecoveryRepository = $passwordRecoveryRepository;
        $this->mail                       = $mail;
        $this->router                     = $router;
    }

    public function find(string $username) : ?PasswordRecovery
    {
        return $this->passwordRecoveryRepository->find($username);
    }

    public function clearExpired()
    {
        $this->passwordRecoveryRepository->clearExpired();
    }

    public function getByToken(string $token) : ?PasswordRecovery
    {
        return $this->passwordRecoveryRepository->getByToken($token);
    }

    public function remove(PasswordRecovery $passwordRecovery)
    {
        $this->passwordRecoveryRepository->remove($passwordRecovery);
    }

    public function sendPasswordRecoveryEmail(string $username)
    {
        $uuid = $this->passwordRecoveryRepository->generateToken($username);

        // Flood protection
        if (!$uuid) {
            return;
        }

        $url = trim(getenv('WEBSITE_URL'), '/').$this->router->generate('password_login_change_password', ['uuid' => $uuid]);

        $this->mail->send(
            $username,
            'password_login.forgot_password.subject',
            '@PasswordLogin/security/forgot_password_mail.txt.twig',
            ['url' => $url, 'type' => 'register']
        );
    }
}