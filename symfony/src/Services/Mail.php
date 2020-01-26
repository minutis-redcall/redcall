<?php

namespace App\Services;

use App\Entity\UserInformation;
use Bundles\PasswordLoginBundle\Services\Mail as BaseMail;

class Mail extends BaseMail
{
    public function send(string $to, string $subject, string $template, array $parameters = [], string $locale = null)
    {
        $preferences = $this->getManager(UserInformation::class)->find($to);
        if ($preferences) {
            $locale = $preferences->getLocale();
        }

        parent::send($to, $subject, $template, $parameters, $locale);
    }
}