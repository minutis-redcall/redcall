<?php

namespace App\Tests\Form\Type;

use App\Form\Type\PhoneCardType;
use App\Form\Type\PhoneCardsType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Test\TypeTestCase;

class PhoneCardsTypeTest extends TypeTestCase
{
    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertSame('phone_cards', $form->getConfig()->getType()->getBlockPrefix());
    }

    public function testParentIsCollectionType(): void
    {
        $type = new PhoneCardsType();
        $this->assertSame(CollectionType::class, $type->getParent());
    }

    public function testDefaultEntryTypeIsPhoneCardType(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertSame(PhoneCardType::class, $form->getConfig()->getOption('entry_type'));
    }

    public function testAllowAdd(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertTrue($form->getConfig()->getOption('allow_add'));
    }

    public function testAllowDelete(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertTrue($form->getConfig()->getOption('allow_delete'));
    }

    public function testByReferenceIsFalse(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertFalse($form->getConfig()->getOption('by_reference'));
    }

    public function testNotRequired(): void
    {
        $form = $this->factory->create(PhoneCardsType::class);
        $this->assertFalse($form->getConfig()->getOption('required'));
    }
}
