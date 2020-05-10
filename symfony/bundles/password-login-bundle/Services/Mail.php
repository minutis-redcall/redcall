<?php

namespace Bundles\PasswordLoginBundle\Services;

use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Mail
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(TranslatorInterface $translator, Environment $twig, \Swift_Mailer $mailer)
    {
        $this->translator = $translator;
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    public function send(string $to, string $subject, string $template, array $parameters = [], string $locale = null)
    {
        $message = (new Swift_Message($this->translator->trans($subject, [], null, $locale)))
            ->setFrom(getenv('MAILER_FROM'))
            ->setTo($to)
            ->setBody(
                $this->twig->render($template, array_merge($parameters, ['_locale' => $locale])),
                'text/plain'
            );

        $this->mailer->send($message);
    }
}

