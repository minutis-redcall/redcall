<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Repository\PasswordRecoveryRepository;
use App\Provider\Email\EmailProvider;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;

class PasswordRecoveryManager
{
    /**
     * @var PasswordRecoveryRepository
     */
    private $passwordRecoveryRepository;

    /**
     * @var EmailProvider
     */
    private $emailProvider;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(PasswordRecoveryRepository $passwordRecoveryRepository,
        EmailProvider $emailProvider,
        Environment $twig,
        TranslatorInterface $translator,
        RouterInterface $router)
    {
        $this->passwordRecoveryRepository = $passwordRecoveryRepository;
        $this->emailProvider              = $emailProvider;
        $this->twig                       = $twig;
        $this->translator                 = $translator;
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

        $url = trim($_ENV['WEBSITE_URL'], '/').$this->router->generate('password_login_change_password', ['uuid' => $uuid]);

        $body = $this->twig->render('@PasswordLogin/security/forgot_password_mail.txt.twig', [
            'url' => $url,
            'type' => 'register',
            '_locale' => null,
        ]);

        $this->emailProvider->send(
            $username,
            $this->translator->trans('password_login.forgot_password.subject'),
            $body,
            $body
        );
    }
}