<?php

namespace App\Tests\Form\Type;

use App\Entity\Choice;
use App\Form\Type\AnswerType;
use Symfony\Component\Form\Test\TypeTestCase;

class AnswerTypeTest extends TypeTestCase
{
    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(AnswerType::class);
        $this->assertSame('answer', $form->getConfig()->getType()->getBlockPrefix());
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(AnswerType::class);
        $form->submit('Yes');

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Yes', $form->getData());
    }

    public function testSubmitEmptyDataSetsNull(): void
    {
        $form = $this->factory->create(AnswerType::class);
        $form->submit('');

        $this->assertTrue($form->isSynchronized());
    }

    public function testMaxLengthAttribute(): void
    {
        $form = $this->factory->create(AnswerType::class);
        $view = $form->createView();

        $this->assertSame(Choice::MAX_LENGTH_DEFAULT, $view->vars['attr']['maxlength']);
    }

    public function testHasLengthConstraint(): void
    {
        $form = $this->factory->create(AnswerType::class);
        $constraints = $form->getConfig()->getOption('constraints');

        $this->assertNotEmpty($constraints);

        $lengthConstraint = null;
        foreach ($constraints as $constraint) {
            if ($constraint instanceof \Symfony\Component\Validator\Constraints\Length) {
                $lengthConstraint = $constraint;
                break;
            }
        }

        $this->assertNotNull($lengthConstraint, 'AnswerType should have a Length constraint');
        $this->assertSame(1, $lengthConstraint->min);
        $this->assertSame(Choice::MAX_LENGTH_DEFAULT, $lengthConstraint->max);
    }
}
