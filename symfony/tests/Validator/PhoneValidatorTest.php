<?php

namespace App\Tests\Validator;

use App\Contract\PhoneInterface;
use App\Manager\PhoneManager;
use App\Validator\Constraints\Phone;
use App\Validator\Constraints\PhoneValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneValidatorTest extends TestCase
{
    private $phoneManager;
    private $translator;
    private $validator;
    private $context;

    protected function setUp() : void
    {
        $this->phoneManager = $this->createMock(PhoneManager::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);
        $this->validator     = new PhoneValidator($this->phoneManager, $this->translator);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testThrowsExceptionForWrongConstraintType()
    {
        $this->expectException(UnexpectedTypeException::class);

        $wrongConstraint = $this->createMock(Constraint::class);
        $phone = $this->createMock(PhoneInterface::class);

        $this->validator->validate($phone, $wrongConstraint);
    }

    public function testThrowsExceptionForWrongValueType()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new Phone();

        $this->validator->validate('not-a-phone-interface', $constraint);
    }

    public function testValidPhoneNumberPassesValidation()
    {
        $constraint = new Phone();

        $phone = $this->createMock(PhoneInterface::class);
        $phone->method('getE164')->willReturn('+33612345678');

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($phone, $constraint);
    }

    public function testInvalidPhoneNumberAddsViolation()
    {
        $constraint = new Phone();

        $phone = $this->createMock(PhoneInterface::class);
        $phone->method('getE164')->willReturn('invalid-phone');

        $this->translator->method('trans')
            ->with('phone_card.error_invalid')
            ->willReturn('Invalid phone number');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->with('editor')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Invalid phone number')
            ->willReturn($violationBuilder);

        $this->validator->validate($phone, $constraint);
    }

    public function testEmptyPhoneNumberAddsViolation()
    {
        $constraint = new Phone();

        $phone = $this->createMock(PhoneInterface::class);
        $phone->method('getE164')->willReturn('');

        $this->translator->method('trans')->willReturn('Invalid phone number');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->method('buildViolation')->willReturn($violationBuilder);

        $this->validator->validate($phone, $constraint);
    }

    public function testInternationalPhoneNumberPassesValidation()
    {
        $constraint = new Phone();

        $phone = $this->createMock(PhoneInterface::class);
        $phone->method('getE164')->willReturn('+447911123456');

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($phone, $constraint);
    }

    public function testPhoneConstraintTargetsClass()
    {
        $constraint = new Phone();

        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
