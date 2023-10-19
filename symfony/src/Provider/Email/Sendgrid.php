<?php

namespace App\Provider\Email;

class Sendgrid implements EmailProvider
{
    public function send(string $to, string $subject, string $textBody, string $htmlBody)
    {
        if (!getenv('SENDGRID_API_KEY')) {
            throw new \LogicException('Sendgrid key is missing in configuration.');
        }

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom(getenv('MAILER_FROM'), getenv('MAILER_NAME'));
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent("text/plain", $textBody);
        $email->addContent("text/html", $htmlBody);

        if (0 !== strpos($htmlBody, 'cid:logo')) {
            $email->addAttachment(base64_encode(file_get_contents(__DIR__.'/../../../public/email.png')), null, 'logo.png', 'inline', 'logo');
        }

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        $sendgrid->send($email);
    }
}