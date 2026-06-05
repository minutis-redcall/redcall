<?php

namespace App\Tests\Captcha;

use App\Captcha\CheckboxCaptchaVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CheckboxCaptchaVerifierTest extends TestCase
{
    public function testVerifyReturnsFalseWhenRequestIsNull() : void
    {
        $verifier = new CheckboxCaptchaVerifier();
        $this->assertFalse($verifier->verify(null));
    }

    public function testVerifyReturnsFalseWhenCheckboxMissing() : void
    {
        $verifier = new CheckboxCaptchaVerifier();
        $request  = new Request();
        $this->assertFalse($verifier->verify($request));
    }

    public function testVerifyReturnsTrueWhenCheckboxChecked() : void
    {
        $verifier = new CheckboxCaptchaVerifier();
        $request  = new Request([], [CheckboxCaptchaVerifier::FIELD_NAME => '1']);
        $this->assertTrue($verifier->verify($request));
    }

    public function testGetWidgetModeReturnsCheckbox() : void
    {
        $verifier = new CheckboxCaptchaVerifier();
        $this->assertSame('checkbox', $verifier->getWidgetMode());
    }
}
