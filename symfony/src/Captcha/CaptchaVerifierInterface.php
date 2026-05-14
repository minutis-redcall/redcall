<?php

namespace App\Captcha;

use Symfony\Component\HttpFoundation\Request;

interface CaptchaVerifierInterface
{
    public function verify(?Request $request) : bool;

    public function getWidgetMode() : string;
}
