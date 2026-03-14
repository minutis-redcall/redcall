<?php

namespace App\Tests\Validator;

use App\Contract\LockableInterface;
use App\Validator\Constraints\Unlocked;
use App\Validator\Constraints\UnlockedValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UnlockedValidatorTest extends TestCase
{
    private $validator;
    private $context;

    protected function setUp() : void
    {
        $this->validator = new UnlockedValidator();
        $this->context   = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testThrowsExceptionForNonLockableValue()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new Unlocked();

        $this->validator->validate('not-lockable', $constraint);
    }

    public function testThrowsExceptionForWrongConstraintType()
    {
        $this->expectException(UnexpectedTypeException::class);

        $lockable = $this->createMock(LockableInterface::class);
        $wrongConstraint = $this->createMock(Constraint::class);

        $this->validator->validate($lockable, $wrongConstraint);
    }

    public function testUnlockedResourcePassesValidation()
    {
        $constraint = new Unlocked();

        $lockable = $this->createMock(LockableInterface::class);
        $lockable->method('isLocked')->willReturn(false);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($lockable, $constraint);
    }

    public function testLockedResourceAddsViolation()
    {
        $constraint = new Unlocked();

        $lockable = $this->createMock(LockableInterface::class);
        $lockable->method('isLocked')->willReturn(true);
        $lockable->method('getDisplayName')->willReturn('John Doe');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')
            ->with('John Doe')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('This resource is locked.')
            ->willReturn($violationBuilder);

        $this->validator->validate($lockable, $constraint);
    }

    public function testNullLockedValuePassesValidation()
    {
        $constraint = new Unlocked();

        $lockable = $this->createMock(LockableInterface::class);
        $lockable->method('isLocked')->willReturn(null);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($lockable, $constraint);
    }

    public function testUnlockedConstraintTargetsClass()
    {
        $constraint = new Unlocked();

        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
