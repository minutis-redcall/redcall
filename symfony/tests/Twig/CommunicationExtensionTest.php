<?php

namespace App\Tests\Twig;

use App\Entity\Communication;
use App\Entity\Media;
use App\Twig\Extension\CommunicationExtension;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\TwigFilter;

class CommunicationExtensionTest extends TestCase
{
    private $extension;

    protected function setUp() : void
    {
        $this->extension = new CommunicationExtension();
    }

    public function testGetFiltersReturnsFormatEmailFilter()
    {
        $filters = $this->extension->getFilters();

        $this->assertIsArray($filters);
        $this->assertNotEmpty($filters);

        $names = array_map(function (TwigFilter $f) {
            return $f->getName();
        }, $filters);

        $this->assertContains('format_email', $names);
    }

    public function testFormatEmailReturnsNullWhenBodyIsNull()
    {
        $environment = $this->createMock(Environment::class);

        $communication = $this->createMock(Communication::class);
        $communication->method('getBody')->willReturn(null);
        $communication->method('getImages')->willReturn(new ArrayCollection());

        $result = $this->extension->formatEmail($environment, $communication);

        $this->assertNull($result);
    }

    public function testFormatEmailReturnsNullWhenBodyIsEmpty()
    {
        $environment = $this->createMock(Environment::class);

        $communication = $this->createMock(Communication::class);
        $communication->method('getBody')->willReturn('');
        $communication->method('getImages')->willReturn(new ArrayCollection());

        $result = $this->extension->formatEmail($environment, $communication);

        $this->assertNull($result);
    }

    public function testFormatEmailReturnsBodyWhenNoImages()
    {
        $environment = $this->createMock(Environment::class);

        $communication = $this->createMock(Communication::class);
        $communication->method('getBody')->willReturn('<p>Hello world</p>');
        $communication->method('getImages')->willReturn(new ArrayCollection());

        $result = $this->extension->formatEmail($environment, $communication);

        $this->assertSame('<p>Hello world</p>', $result);
    }

    public function testFormatEmailReplacesImagePlaceholders()
    {
        $environment = $this->createMock(Environment::class);

        $imageUuid = 'abc-123-def';
        $imageUrl  = 'https://storage.example.com/image.jpg';

        $media = $this->createMock(Media::class);
        $media->method('getUuid')->willReturn($imageUuid);
        $media->method('getUrl')->willReturn($imageUrl);

        $communication = $this->createMock(Communication::class);
        $communication->method('getBody')->willReturn(
            '<p>Before image</p>{image:abc-123-def}<p>After image</p>'
        );
        $communication->method('getImages')->willReturn(new ArrayCollection([$media]));

        // The twig render returns a full email template with image markers
        $environment->method('render')
            ->with('message/image.html.twig', ['url' => $imageUrl])
            ->willReturn('prefix<!-- image:begin --><img src="'.$imageUrl.'"/><!-- image:end -->suffix');

        $result = $this->extension->formatEmail($environment, $communication);

        $this->assertStringContainsString('<p>Before image</p>', $result);
        $this->assertStringContainsString('<p>After image</p>', $result);
        $this->assertStringContainsString('<img src="'.$imageUrl.'"/>', $result);
        $this->assertStringNotContainsString('{image:abc-123-def}', $result);
    }

    public function testFormatEmailHandlesMultipleImages()
    {
        $environment = $this->createMock(Environment::class);

        $media1 = $this->createMock(Media::class);
        $media1->method('getUuid')->willReturn('uuid-1');
        $media1->method('getUrl')->willReturn('https://example.com/img1.jpg');

        $media2 = $this->createMock(Media::class);
        $media2->method('getUuid')->willReturn('uuid-2');
        $media2->method('getUrl')->willReturn('https://example.com/img2.jpg');

        $communication = $this->createMock(Communication::class);
        $communication->method('getBody')->willReturn(
            'Text {image:uuid-1} middle {image:uuid-2} end'
        );
        $communication->method('getImages')->willReturn(new ArrayCollection([$media1, $media2]));

        $environment->method('render')
            ->willReturnCallback(function ($template, $params) {
                return 'x<!-- image:begin --><img src="'.$params['url'].'"/><!-- image:end -->y';
            });

        $result = $this->extension->formatEmail($environment, $communication);

        $this->assertStringContainsString('<img src="https://example.com/img1.jpg"/>', $result);
        $this->assertStringContainsString('<img src="https://example.com/img2.jpg"/>', $result);
        $this->assertStringNotContainsString('{image:uuid-1}', $result);
        $this->assertStringNotContainsString('{image:uuid-2}', $result);
    }

    public function testFormatEmailFilterNeedsEnvironment()
    {
        $filters = $this->extension->getFilters();

        foreach ($filters as $filter) {
            if ($filter->getName() === 'format_email') {
                $this->assertTrue($filter->needsEnvironment());
                return;
            }
        }

        $this->fail('format_email filter not found');
    }

    public function testFormatEmailFilterIsSafeForHtml()
    {
        $filters = $this->extension->getFilters();

        foreach ($filters as $filter) {
            if ($filter->getName() === 'format_email') {
                // Use a Twig Node to satisfy the getSafe signature
                $node = new \Twig\Node\Node();
                $safe = $filter->getSafe($node);
                $this->assertContains('html', $safe);
                return;
            }
        }

        $this->fail('format_email filter not found');
    }
}
