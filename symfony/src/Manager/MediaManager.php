<?php

namespace App\Manager;

use App\Entity\Media;
use App\Provider\Storage\StorageProvider;
use App\Repository\MediaRepository;
use App\Services\TextToSpeech;
use Ramsey\Uuid\Uuid;

class MediaManager
{
    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var TextToSpeech
     */
    private $textToSpeech;

    /**
     * @var StorageProvider
     */
    private $storage;

    public function __construct(MediaRepository $mediaRepository, TextToSpeech $textToSpeech, StorageProvider $storage)
    {
        $this->mediaRepository = $mediaRepository;
        $this->textToSpeech    = $textToSpeech;
        $this->storage         = $storage;
    }

    public function createMedia(string $extension, string $text) : Media
    {
        $callback = function ($text) {
            return $text;
        };

        return $this->getMedia($extension, $text, $callback);
    }

    public function createMp3(string $text, bool $male = false) : Media
    {
        $callback = function ($text) use ($male) {
            return $this->textToSpeech->textToSpeech($text, $male);
        };

        return $this->getMedia('mp3', $text, $callback);
    }

    public function clearExpired()
    {
        $this->mediaRepository->clearExpired();
    }

    public function save(Media $media)
    {
        $this->mediaRepository->save($media);
    }

    private function getMedia(string $extension, string $text, callable $callback)
    {
        /** @var Media $media */
        if ($media = $this->findOneByText($text)) {
            return $media;
        }

        $media = new Media();
        $media->setUuid(Uuid::uuid4());
        $media->setHash(hash('SHA256', $text));

        $filename = sprintf('%s.%s', $media->getUuid(), $extension);

        $url = $this->storage->store($filename, $callback($text));

        $media->setUrl($url);
        $media->setCreatedAt(new \DateTime());
        $media->setExpiresAt((new \DateTime())->add(new \DateInterval(sprintf('P%dD', $this->storage->getRetentionDays()))));

        $this->mediaRepository->save($media);

        return $media;
    }

    private function findOneByText(string $text) : ?Media
    {
        /** @var Media|null $media */
        $media = $this->mediaRepository->findOneByHash(
            hash('SHA256', $text)
        );

        if (!$media) {
            return null;
        }

        return $media;
    }
}