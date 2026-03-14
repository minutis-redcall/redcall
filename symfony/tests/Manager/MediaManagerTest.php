<?php

namespace App\Tests\Manager;

use App\Entity\Media;
use App\Manager\MediaManager;
use App\Provider\Storage\StorageProvider;
use App\Repository\MediaRepository;
use App\Services\TextToSpeech;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class MediaManagerTest extends TestCase
{
    private function createMediaRepositoryMock(array $methods = ['findOneByHash', 'save', 'clearExpired'])
    {
        return $this->getMockBuilder(MediaRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(array_diff($methods, get_class_methods(MediaRepository::class)))
            ->onlyMethods(array_intersect($methods, get_class_methods(MediaRepository::class)))
            ->getMock();
    }

    public function testCreateMediaReturnsMediaWithCorrectHash()
    {
        $text = 'test content';
        $expectedHash = hash('SHA256', $text);

        $mediaRepository = $this->createMediaRepositoryMock();
        $mediaRepository->method('findOneByHash')->willReturn(null);
        $mediaRepository->expects($this->once())->method('save');

        $storageProvider = $this->createMock(StorageProvider::class);
        $storageProvider->method('store')->willReturn('https://storage.example.com/file.txt');
        $storageProvider->method('getRetentionDays')->willReturn(30);

        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);

        $media = $manager->createMedia('txt', $text);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertSame($expectedHash, $media->getHash());
        $this->assertSame('https://storage.example.com/file.txt', $media->getUrl());
    }

    public function testCreateMediaReturnsCachedMediaIfExists()
    {
        $text = 'cached content';

        $existingMedia = new Media();
        $existingMedia->setUuid(Uuid::uuid4());
        $existingMedia->setHash(hash('SHA256', $text));
        $existingMedia->setUrl('https://storage.example.com/cached.txt');
        $existingMedia->setCreatedAt(new \DateTime());

        $mediaRepository = $this->createMediaRepositoryMock();
        $mediaRepository->method('findOneByHash')
            ->with(hash('SHA256', $text))
            ->willReturn($existingMedia);
        $mediaRepository->expects($this->never())->method('save');

        $storageProvider = $this->createMock(StorageProvider::class);
        $storageProvider->expects($this->never())->method('store');

        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);

        $media = $manager->createMedia('txt', $text);

        $this->assertSame($existingMedia, $media);
    }

    public function testCreateMediaSetsExpirationDate()
    {
        $text = 'expiring content';

        $mediaRepository = $this->createMediaRepositoryMock();
        $mediaRepository->method('findOneByHash')->willReturn(null);

        $storageProvider = $this->createMock(StorageProvider::class);
        $storageProvider->method('store')->willReturn('https://storage.example.com/file.txt');
        $storageProvider->method('getRetentionDays')->willReturn(7);

        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);

        $media = $manager->createMedia('txt', $text);

        $this->assertNotNull($media->getExpiresAt());
        // Expires should be approximately 7 days from now
        $diff = $media->getExpiresAt()->diff(new \DateTime());
        $this->assertGreaterThanOrEqual(6, $diff->days);
        $this->assertLessThanOrEqual(8, $diff->days);
    }

    public function testCreateMediaSetsUuid()
    {
        $text = 'uuid content';

        $mediaRepository = $this->createMediaRepositoryMock();
        $mediaRepository->method('findOneByHash')->willReturn(null);

        $storageProvider = $this->createMock(StorageProvider::class);
        $storageProvider->method('store')->willReturn('https://example.com/file.mp3');
        $storageProvider->method('getRetentionDays')->willReturn(30);

        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);

        $media = $manager->createMedia('mp3', $text);

        $this->assertNotNull($media->getUuid());
        $this->assertTrue(Uuid::isValid($media->getUuid()));
    }

    public function testCreateMediaStoresWithCorrectFilename()
    {
        $text = 'filename content';

        $mediaRepository = $this->createMediaRepositoryMock();
        $mediaRepository->method('findOneByHash')->willReturn(null);

        $storageProvider = $this->createMock(StorageProvider::class);
        $storageProvider->method('getRetentionDays')->willReturn(30);
        $storageProvider->expects($this->once())
            ->method('store')
            ->with(
                $this->matchesRegularExpression('/^[a-f0-9\-]+\.wav$/'),
                $text
            )
            ->willReturn('https://example.com/stored.wav');

        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);

        $media = $manager->createMedia('wav', $text);

        $this->assertSame('https://example.com/stored.wav', $media->getUrl());
    }

    public function testClearExpired()
    {
        $mediaRepository = $this->createMock(MediaRepository::class);
        $mediaRepository->expects($this->once())->method('clearExpired');

        $storageProvider = $this->createMock(StorageProvider::class);
        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);
        $manager->clearExpired();
    }

    public function testSave()
    {
        $media = new Media();

        $mediaRepository = $this->createMock(MediaRepository::class);
        $mediaRepository->expects($this->once())->method('save')->with($media);

        $storageProvider = $this->createMock(StorageProvider::class);
        $textToSpeech = $this->createMock(TextToSpeech::class);

        $manager = new MediaManager($mediaRepository, $textToSpeech, $storageProvider);
        $manager->save($media);
    }
}
