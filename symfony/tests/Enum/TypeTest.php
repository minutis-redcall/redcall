<?php

namespace App\Tests\Enum;

use App\Enum\Type;
use App\Form\Flow\CallTriggerFlow;
use App\Form\Flow\EmailTriggerFlow;
use App\Form\Flow\SmsTriggerFlow;
use App\Form\Model\CallTrigger;
use App\Form\Model\EmailTrigger;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CallTriggerType;
use App\Form\Type\EmailTriggerType;
use App\Form\Type\SmsTriggerType;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testSmsValue(): void
    {
        $this->assertSame('sms', Type::SMS()->getValue());
    }

    public function testCallValue(): void
    {
        $this->assertSame('call', Type::CALL()->getValue());
    }

    public function testEmailValue(): void
    {
        $this->assertSame('email', Type::EMAIL()->getValue());
    }

    public function testAllValuesExist(): void
    {
        $values = Type::toArray();
        $this->assertCount(3, $values);
        $this->assertContains('sms', $values);
        $this->assertContains('call', $values);
        $this->assertContains('email', $values);
    }

    // --- getFormType ---

    public function testGetFormTypeForSms(): void
    {
        $this->assertSame(SmsTriggerType::class, Type::SMS()->getFormType());
    }

    public function testGetFormTypeForCall(): void
    {
        $this->assertSame(CallTriggerType::class, Type::CALL()->getFormType());
    }

    public function testGetFormTypeForEmail(): void
    {
        $this->assertSame(EmailTriggerType::class, Type::EMAIL()->getFormType());
    }

    // --- getFormData ---

    public function testGetFormDataForSms(): void
    {
        $this->assertInstanceOf(SmsTrigger::class, Type::SMS()->getFormData());
    }

    public function testGetFormDataForCall(): void
    {
        $this->assertInstanceOf(CallTrigger::class, Type::CALL()->getFormData());
    }

    public function testGetFormDataForEmail(): void
    {
        $this->assertInstanceOf(EmailTrigger::class, Type::EMAIL()->getFormData());
    }

    // --- getFormFlow ---

    public function testGetFormFlowForSms(): void
    {
        $this->assertSame(SmsTriggerFlow::class, Type::SMS()->getFormFlow());
    }

    public function testGetFormFlowForCall(): void
    {
        $this->assertSame(CallTriggerFlow::class, Type::CALL()->getFormFlow());
    }

    public function testGetFormFlowForEmail(): void
    {
        $this->assertSame(EmailTriggerFlow::class, Type::EMAIL()->getFormFlow());
    }

    // --- getFormView ---

    public function testGetFormViewForSms(): void
    {
        $this->assertSame('new_communication/form_sms.html.twig', Type::SMS()->getFormView());
    }

    public function testGetFormViewForCall(): void
    {
        $this->assertSame('new_communication/form_call.html.twig', Type::CALL()->getFormView());
    }

    public function testGetFormViewForEmail(): void
    {
        $this->assertSame('new_communication/form_email.html.twig', Type::EMAIL()->getFormView());
    }

    // --- isSms / isCall / isEmail ---

    public function testIsSms(): void
    {
        $this->assertTrue(Type::SMS()->isSms());
        $this->assertFalse(Type::CALL()->isSms());
        $this->assertFalse(Type::EMAIL()->isSms());
    }

    public function testIsCall(): void
    {
        $this->assertFalse(Type::SMS()->isCall());
        $this->assertTrue(Type::CALL()->isCall());
        $this->assertFalse(Type::EMAIL()->isCall());
    }

    public function testIsEmail(): void
    {
        $this->assertFalse(Type::SMS()->isEmail());
        $this->assertFalse(Type::CALL()->isEmail());
        $this->assertTrue(Type::EMAIL()->isEmail());
    }

    // --- Enum behavior ---

    public function testEquality(): void
    {
        $this->assertTrue(Type::SMS()->equals(Type::SMS()));
        $this->assertFalse(Type::SMS()->equals(Type::CALL()));
    }

    public function testIsValid(): void
    {
        $this->assertTrue(Type::isValid('sms'));
        $this->assertTrue(Type::isValid('call'));
        $this->assertTrue(Type::isValid('email'));
        $this->assertFalse(Type::isValid('SMS'));
        $this->assertFalse(Type::isValid('invalid'));
    }

    public function testToString(): void
    {
        $this->assertSame('sms', (string) Type::SMS());
        $this->assertSame('call', (string) Type::CALL());
        $this->assertSame('email', (string) Type::EMAIL());
    }
}
