<?php

namespace App\Services;

use App\Manager\UserInformationManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Bundles\PasswordLoginBundle\Services\Mail as BaseMail;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class Mail extends BaseMail
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param TranslatorInterface    $translator
     * @param Environment            $twig
     * @param \Swift_Mailer          $mailer
     * @param UserManager            $userManager
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(TranslatorInterface $translator, Environment $twig, \Swift_Mailer $mailer, UserManager $userManager, UserInformationManager $userInformationManager)
    {
        parent::__construct($translator, $twig, $mailer);

        $this->userManager = $userManager;
        $this->userInformationManager = $userInformationManager;
    }

    public function send(string $to, string $subject, string $template, array $parameters = [], string $locale = null)
    {
        $user = $this->userManager->findOneByUsername($to);
        if ($user) {
            $preferences = $this->userInformationManager->findOneByUser($user);
            if ($preferences) {
                $locale = $preferences->getLocale();
            }
        }

        parent::send($to, $subject, $template, $parameters, $locale);
    }
}