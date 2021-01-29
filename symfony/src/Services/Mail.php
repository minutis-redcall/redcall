<?php

namespace App\Services;

use Bundles\PasswordLoginBundle\Manager\UserManager;
use Bundles\PasswordLoginBundle\Services\Mail as BaseMail;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Mail extends BaseMail
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param TranslatorInterface $translator
     * @param Environment         $twig
     * @param \Swift_Mailer       $mailer
     * @param UserManager         $userManager
     */
    public function __construct(TranslatorInterface $translator,
        Environment $twig,
        \Swift_Mailer $mailer,
        UserManager $userManager)
    {
        parent::__construct($translator, $twig, $mailer);

        $this->userManager = $userManager;
    }

    public function send(string $to, string $subject, string $template, array $parameters = [], string $locale = null)
    {
        if ($user = $this->userManager->findOneByUsername($to)) {
            $locale = $user->getLocale();
        }

        parent::send($to, $subject, $template, $parameters, $locale);
    }
}