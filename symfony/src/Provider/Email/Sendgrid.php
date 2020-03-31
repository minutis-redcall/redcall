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
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            //            print $response->statusCode() . "\n";
            //            print_r($response->headers());
            //            print $response->body() . "\n";
        } catch (\Exception $e) {
            //            echo 'Caught exception: '. $e->getMessage() ."\n";
            // @TODO issue #183
        }
    }
}