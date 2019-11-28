<?php

namespace App\Provider\Email;

class SymfonyEmailProvider implements EmailProvider
{
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(string $to, string $subject, string $body)
    {
        $email = (new \Swift_Message($subject))
            ->setFrom(getenv('MAILER_FROM'))
            ->setTo($to)
            ->setBody($body, 'text/plain');

        $this->mailer->send($email);
    }
}