<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Entity\Template;
use App\Entity\TemplateImage;
use App\Manager\TemplateImageManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TemplateImageManagerTest extends KernelTestCase
{
    private TemplateImageManager $manager;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $container      = static::getContainer();
        $this->manager  = $container->get(TemplateImageManager::class);
        $this->fixtures = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    /**
     * Creates a minimal valid 1x1 red PNG image and returns its base64-encoded content.
     */
    private function createBase64PngImage() : string
    {
        $gd = imagecreatetruecolor(1, 1);
        $red = imagecolorallocate($gd, 255, 0, 0);
        imagesetpixel($gd, 0, 0, $red);
        ob_start();
        imagepng($gd);
        $binary = ob_get_clean();
        imagedestroy($gd);

        return base64_encode($binary);
    }

    public function testHandleImagesWithNoImages()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT', 'EXT-IMG-001');
        $template  = $this->fixtures->createTemplate($structure, 'No Images', Communication::TYPE_EMAIL, '<p>No images here</p>');

        $result = $this->manager->handleImages($template, '<p>No images here</p>');

        $this->assertSame('<p>No images here</p>', $result);
        $this->assertCount(0, $template->getImages());
    }

    public function testHandleImagesExtractsAndReplacesBase64Images()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT2', 'EXT-IMG-002');
        $template  = $this->fixtures->createTemplate($structure, 'With Image', Communication::TYPE_EMAIL, '<p>placeholder</p>');

        $base64 = $this->createBase64PngImage();
        $body   = sprintf('<p>Hello</p><img src="data:image/png;base64,%s"><p>World</p>', $base64);

        $result = $this->manager->handleImages($template, $body);

        // The body should no longer contain base64 data
        $this->assertStringNotContainsString('data:image/png;base64,', $result);

        // The body should contain the image placeholder
        $this->assertMatchesRegularExpression('/\{image:[0-9a-f\-]+\}/', $result);

        // Template should have one image
        $this->assertCount(1, $template->getImages());

        // Image content should be valid base64 PNG
        $image = $template->getImages()->first();
        $this->assertNotEmpty($image->getContent());
        $this->assertNotEmpty($image->getUuid());
    }

    public function testHandleImagesWithMultipleImages()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT3', 'EXT-IMG-003');
        $template  = $this->fixtures->createTemplate($structure, 'Multi Image', Communication::TYPE_EMAIL, '<p>placeholder</p>');

        $base64a = $this->createBase64PngImage();
        $base64b = $this->createBase64PngImage();
        $body    = sprintf(
            '<img src="data:image/png;base64,%s"><br><img src="data:image/png;base64,%s">',
            $base64a,
            $base64b
        );

        $result = $this->manager->handleImages($template, $body);

        $this->assertStringNotContainsString('data:image/png;base64,', $result);

        // Should have 2 image placeholders
        preg_match_all('/\{image:[0-9a-f\-]+\}/', $result, $matches);
        $this->assertCount(2, $matches[0]);

        $this->assertCount(2, $template->getImages());
    }

    public function testHandleImagesClearsExistingImagesFirst()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT4', 'EXT-IMG-004');
        $template  = $this->fixtures->createTemplate($structure, 'Clear Image', Communication::TYPE_EMAIL, '<p>placeholder</p>');

        // Add an existing image to the template
        $existingImage = new TemplateImage();
        $existingImage->setContent('old-content');
        $template->addImage($existingImage);
        $this->assertCount(1, $template->getImages());

        // Call handleImages with no base64 images in body
        $result = $this->manager->handleImages($template, '<p>No images</p>');

        // Existing images should have been removed
        $this->assertCount(0, $template->getImages());
    }

    public function testHandleImagesSkipsInvalidBase64()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT5', 'EXT-IMG-005');
        $template  = $this->fixtures->createTemplate($structure, 'Invalid Image', Communication::TYPE_EMAIL, '<p>placeholder</p>');

        // Invalid base64 that decodes to non-image data
        $invalidBase64 = base64_encode('not an image');
        $body = sprintf('<img src="data:image/png;base64,%s">', $invalidBase64);

        $result = $this->manager->handleImages($template, $body);

        // The invalid image should be skipped (imagecreatefromstring returns false)
        $this->assertCount(0, $template->getImages());
    }

    public function testHandleImagesWithJpegType()
    {
        $structure = $this->fixtures->createStructure('IMG STRUCT6', 'EXT-IMG-006');
        $template  = $this->fixtures->createTemplate($structure, 'JPEG Image', Communication::TYPE_EMAIL, '<p>placeholder</p>');

        // Create a JPEG image
        $gd = imagecreatetruecolor(2, 2);
        $blue = imagecolorallocate($gd, 0, 0, 255);
        imagefill($gd, 0, 0, $blue);
        ob_start();
        imagejpeg($gd);
        $binary = ob_get_clean();
        imagedestroy($gd);
        $base64 = base64_encode($binary);

        $body = sprintf('<img src="data:image/jpeg;base64,%s">', $base64);

        $result = $this->manager->handleImages($template, $body);

        // JPEG images should be handled (converted to PNG in output)
        $this->assertCount(1, $template->getImages());
        $this->assertMatchesRegularExpression('/\{image:[0-9a-f\-]+\}/', $result);
    }
}
