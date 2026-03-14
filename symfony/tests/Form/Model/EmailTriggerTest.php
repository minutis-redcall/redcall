<?php

namespace App\Tests\Form\Model;

use App\Entity\Communication;
use App\Entity\Media;
use App\Form\Model\EmailTrigger;
use PHPUnit\Framework\TestCase;

class EmailTriggerTest extends TestCase
{
    public function testConstructorSetsTypeEmail(): void
    {
        $trigger = new EmailTrigger();
        $this->assertSame(Communication::TYPE_EMAIL, $trigger->getType());
    }

    public function testConstructorInitializesAudienceData(): void
    {
        $trigger = new EmailTrigger();
        $audience = $trigger->getAudience();
        $this->assertIsArray($audience);
        $this->assertArrayHasKey('volunteers', $audience);
    }

    // --- subject ---

    public function testSubjectDefaultsToNull(): void
    {
        $trigger = new EmailTrigger();
        $this->assertNull($trigger->getSubject());
    }

    public function testSetAndGetSubject(): void
    {
        $trigger = new EmailTrigger();
        $result = $trigger->setSubject('Test Subject');
        $this->assertSame('Test Subject', $trigger->getSubject());
        $this->assertSame($trigger, $result);
    }

    public function testSetSubjectToNull(): void
    {
        $trigger = new EmailTrigger();
        $trigger->setSubject('Subject');
        $trigger->setSubject(null);
        $this->assertNull($trigger->getSubject());
    }

    // --- images ---

    public function testImagesDefaultsToEmptyArray(): void
    {
        $trigger = new EmailTrigger();
        $this->assertSame([], $trigger->getImages());
    }

    public function testAddImage(): void
    {
        $trigger = new EmailTrigger();
        $media = $this->createMock(Media::class);
        $result = $trigger->addImage($media);
        $this->assertCount(1, $trigger->getImages());
        $this->assertSame($media, $trigger->getImages()[0]);
        $this->assertSame($trigger, $result);
    }

    public function testAddMultipleImages(): void
    {
        $trigger = new EmailTrigger();
        $media1 = $this->createMock(Media::class);
        $media2 = $this->createMock(Media::class);
        $trigger->addImage($media1);
        $trigger->addImage($media2);
        $this->assertCount(2, $trigger->getImages());
    }

    // --- jsonSerialize ---

    public function testJsonSerializeIncludesImageUuids(): void
    {
        $trigger = new EmailTrigger();
        $trigger->setLabel('Email');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Content');
        $trigger->setSubject('Subject');

        $media = $this->createMock(Media::class);
        $media->method('getUuid')->willReturn('uuid-123');
        $trigger->addImage($media);

        $json = $trigger->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame(['uuid-123'], $json['images']);
        // Note: subject is private in EmailTrigger, not visible to get_object_vars
        // called from parent BaseTrigger, so it is not included in the serialized output
        $this->assertArrayNotHasKey('subject', $json);
    }

    public function testJsonSerializeWithNoImages(): void
    {
        $trigger = new EmailTrigger();
        $trigger->setLabel('Email');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Content');

        $json = $trigger->jsonSerialize();
        $this->assertSame([], $json['images']);
    }

    // --- inherited methods ---

    public function testInheritsBaseTriggerMethods(): void
    {
        $trigger = new EmailTrigger();
        $trigger->setLabel('Test Email');
        $trigger->setLanguage('en');
        $trigger->setMessage('<p>HTML content</p>');
        $trigger->setAnswers(['Yes', 'No']);
        $trigger->setMultipleAnswer(true);

        $this->assertSame('Test Email', $trigger->getLabel());
        $this->assertSame('en', $trigger->getLanguage());
        $this->assertSame('<p>HTML content</p>', $trigger->getMessage());
        $this->assertSame(['Yes', 'No'], $trigger->getAnswers());
        $this->assertTrue($trigger->isMultipleAnswer());
    }
}
