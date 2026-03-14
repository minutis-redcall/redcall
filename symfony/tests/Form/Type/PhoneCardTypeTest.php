<?php

namespace App\Tests\Form\Type;

use App\Entity\Phone;
use App\Form\Type\PhoneCardType;
use Symfony\Component\Form\Test\TypeTestCase;

class PhoneCardTypeTest extends TypeTestCase
{
    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(PhoneCardType::class);
        $this->assertSame('phone_card', $form->getConfig()->getType()->getBlockPrefix());
    }

    public function testFormHasEditorField(): void
    {
        $form = $this->factory->create(PhoneCardType::class);
        $this->assertTrue($form->has('editor'));
    }

    public function testFormHasE164Field(): void
    {
        $form = $this->factory->create(PhoneCardType::class);
        $this->assertTrue($form->has('e164'));
    }

    public function testFormHasPreferredField(): void
    {
        $form = $this->factory->create(PhoneCardType::class);
        $this->assertTrue($form->has('preferred'));
    }

    public function testDataClassIsPhone(): void
    {
        $form = $this->factory->create(PhoneCardType::class);
        $this->assertSame(Phone::class, $form->getConfig()->getOption('data_class'));
    }

    public function testSubmitWithValidData(): void
    {
        $form = $this->factory->create(PhoneCardType::class);

        $form->submit([
            'editor'    => '+33612345678',
            'e164'      => '+33612345678',
            'preferred' => true,
        ]);

        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitWithMinimalData(): void
    {
        $form = $this->factory->create(PhoneCardType::class);

        $form->submit([
            'editor' => '',
            'e164'   => '',
        ]);

        $this->assertTrue($form->isSynchronized());
    }
}
