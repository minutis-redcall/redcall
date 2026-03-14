<?php

namespace App\Tests\Entity;

use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Template;
use App\Entity\TemplateImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class TemplateTest extends TestCase
{
    public function testGetBodyWithImagesNoImages(): void
    {
        $template = new Template();
        $template->setBody('Hello, this is a plain body.');

        $this->assertSame('Hello, this is a plain body.', $template->getBodyWithImages());
    }

    public function testGetBodyWithImagesReplacesPlaceholders(): void
    {
        $template = new Template();

        $image = new TemplateImage();
        $image->setUuid('abc-123');
        $image->setContent('base64data==');

        $template->setBody('Before {image:abc-123} After');
        $template->addImage($image);

        $result = $template->getBodyWithImages();

        $this->assertSame(
            'Before <img src="data:image/png;base64, base64data=="/> After',
            $result
        );
    }

    public function testGetBodyWithImagesMultipleImages(): void
    {
        $template = new Template();

        $image1 = new TemplateImage();
        $image1->setUuid('uuid-1');
        $image1->setContent('data1');

        $image2 = new TemplateImage();
        $image2->setUuid('uuid-2');
        $image2->setContent('data2');

        $template->setBody('{image:uuid-1} middle {image:uuid-2}');
        $template->addImage($image1);
        $template->addImage($image2);

        $result = $template->getBodyWithImages();

        $this->assertStringContainsString('data:image/png;base64, data1', $result);
        $this->assertStringContainsString('data:image/png;base64, data2', $result);
        $this->assertStringContainsString('middle', $result);
        $this->assertStringNotContainsString('{image:', $result);
    }

    public function testAddImageSetsTemplateOnImage(): void
    {
        $template = new Template();
        $image = new TemplateImage();

        $result = $template->addImage($image);

        $this->assertSame($template, $result);
        $this->assertCount(1, $template->getImages());
        $this->assertSame($template, $image->getTemplate());
    }

    public function testAddImageDoesNotDuplicate(): void
    {
        $template = new Template();
        $image = new TemplateImage();

        $template->addImage($image);
        $template->addImage($image);

        $this->assertCount(1, $template->getImages());
    }

    public function testRemoveImageUnsetsTemplateOnImage(): void
    {
        $template = new Template();
        $image = new TemplateImage();

        $template->addImage($image);
        $result = $template->removeImage($image);

        $this->assertSame($template, $result);
        $this->assertCount(0, $template->getImages());
        $this->assertNull($image->getTemplate());
    }

    public function testRemoveImageDoesNotUnsetTemplateIfAlreadyChanged(): void
    {
        $template1 = new Template();
        $template2 = new Template();
        $image = new TemplateImage();

        $template1->addImage($image);
        $image->setTemplate($template2);
        $template1->removeImage($image);

        $this->assertSame($template2, $image->getTemplate());
    }

    public function testRemoveImageThatDoesNotExistIsNoOp(): void
    {
        $template = new Template();
        $image = new TemplateImage();

        $result = $template->removeImage($image);

        $this->assertSame($template, $result);
    }

    private function createValidationContext(int $expectedViolations = 0): ExecutionContextInterface
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('atPath')->willReturn($builder);
        $builder->expects($expectedViolations > 0 ? $this->atLeast(1) : $this->never())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        if ($expectedViolations > 0) {
            $context->method('buildViolation')->willReturn($builder);
        } else {
            $context->expects($this->never())->method('buildViolation');
        }

        return $context;
    }

    public function testValidateSmsWithValidBody(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_SMS);
        $template->setBody('Short message');
        $template->setAnswers(['OK']);

        $context = $this->createValidationContext(0);
        $template->validate($context, null);
    }

    public function testValidateSmsWithTooLargeBody(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_SMS);
        $template->setBody(str_repeat('x', Message::MAX_LENGTH_SMS + 1));
        $template->setAnswers(['OK']);

        $context = $this->createValidationContext(1);
        $template->validate($context, null);
    }

    public function testValidateSmsWithTooLargeChoice(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_SMS);
        $template->setBody('Short');
        $template->setAnswers([str_repeat('A', Choice::MAX_LENGTH_SMS + 1)]);

        $context = $this->createValidationContext(1);
        $template->validate($context, null);
    }

    public function testValidateCallWithTooLargeBody(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_CALL);
        $template->setBody(str_repeat('x', Message::MAX_LENGTH_CALL + 1));
        $template->setAnswers([]);

        $context = $this->createValidationContext(1);
        $template->validate($context, null);
    }

    public function testValidateCallWithValidBody(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_CALL);
        $template->setBody('Valid call body');
        $template->setAnswers([]);

        $context = $this->createValidationContext(0);
        $template->validate($context, null);
    }

    public function testValidateEmailWithNoSubject(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_EMAIL);
        $template->setBody('Email body');
        $template->setAnswers([]);

        $context = $this->createValidationContext(1);
        $template->validate($context, null);
    }

    public function testValidateEmailWithTooLargeBody(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_EMAIL);
        $template->setSubject('Subject');
        $template->setBody(str_repeat('x', Message::MAX_LENGTH_EMAIL + 1));
        $template->setAnswers([]);

        $context = $this->createValidationContext(1);
        $template->validate($context, null);
    }

    public function testValidateEmailValid(): void
    {
        $template = new Template();
        $template->setType(Communication::TYPE_EMAIL);
        $template->setSubject('Subject');
        $template->setBody('Short email body');
        $template->setAnswers([]);

        $context = $this->createValidationContext(0);
        $template->validate($context, null);
    }
}
