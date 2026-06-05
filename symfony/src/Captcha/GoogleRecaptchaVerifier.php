<?php

namespace App\Captcha;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    private ReCaptcha       $recaptcha;
    private LoggerInterface $logger;

    public function __construct(ReCaptcha $recaptcha, ?LoggerInterface $logger = null)
    {
        $this->recaptcha = $recaptcha;
        $this->logger    = $logger ?? new NullLogger();
    }

    public function verify(?Request $request) : bool
    {
        $token = $request ? (string) $request->request->get('g-recaptcha-response') : '';
        $ip    = $request ? $request->getClientIp() : null;

        $response = $this->recaptcha->verify($token, $ip);

        if (!$response->isSuccess()) {
            $this->logger->warning('reCAPTCHA verification failed', [
                'error_codes' => $response->getErrorCodes(),
                'hostname'    => $response->getHostname(),
                'ip'          => $ip,
                'token_empty' => '' === $token,
            ]);
        }

        return $response->isSuccess();
    }

    public function getWidgetMode() : string
    {
        return 'google';
    }
}
