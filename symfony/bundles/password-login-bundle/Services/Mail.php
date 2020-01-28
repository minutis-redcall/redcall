<?php

namespace Bundles\PasswordLoginBundle\Services;

use Bundles\PasswordLoginBundle\Base\BaseService;

class Mail extends BaseService
{
    public function send(string $to, string $subject, string $template, array $parameters = [], string $locale = null)
    {
        $message = (new \Swift_Message($this->trans($subject, [], null, $locale)))
            ->setFrom(getenv('MAILER_FROM'))
            ->setTo($to)
            ->setBody(
                $this->get('twig')->render($template, array_merge($parameters, ['_locale' => $locale])),
                'text/plain'
            );

        $this->get('mailer')->send($message);
    }
}

