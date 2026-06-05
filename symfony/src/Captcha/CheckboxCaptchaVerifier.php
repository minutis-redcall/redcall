<?php

namespace App\Captcha;

use Symfony\Component\HttpFoundation\Request;

class CheckboxCaptchaVerifier implements CaptchaVerifierInterface
{
    public const FIELD_NAME = 'captcha_checkbox';

    public function verify(?Request $request) : bool
    {
        if (!$request) {
            return false;
        }

        return (bool) $request->request->get(self::FIELD_NAME);
    }

    public function getWidgetMode() : string
    {
        return 'checkbox';
    }
}
