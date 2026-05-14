<?php

namespace App\Captcha;

use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    private ReCaptcha $recaptcha;

    public function __construct(ReCaptcha $recaptcha)
    {
        $this->recaptcha = $recaptcha;
    }

    public function verify(?Request $request) : bool
    {
        $token = $request ? $request->request->get('g-recaptcha-response') : null;
        $ip    = $request ? $request->getClientIp() : null;

        return $this->recaptcha->verify((string) $token, $ip)->isSuccess();
    }

    public function getWidgetMode() : string
    {
        return 'google';
    }
}
