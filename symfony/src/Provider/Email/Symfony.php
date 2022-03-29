<?php

namespace App\Provider\Email;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class Symfony implements EmailProvider
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(string $to, string $subject, string $textBody, string $htmlBody)
    {
        $email = (new Email())
            ->from(getenv('MAILER_FROM'))
            ->to($to)
            ->subject($subject)
            ->text($textBody)
            ->html($htmlBody)
            ->embedFromPath(__DIR__.'/../../../public/email.png', 'logo');

        $this->mailer->send($email);
    }
}
