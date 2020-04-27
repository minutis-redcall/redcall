<?php

namespace App\Manager;

use App\Entity\Media;
use App\Repository\MediaRepository;
use App\Services\Storage;
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
     * @var Storage
     */
    private $storage;

    /**
     * @param MediaRepository $mediaRepository
     * @param TextToSpeech    $textToSpeech
     * @param Storage         $storage
     */
    public function __construct(MediaRepository $mediaRepository, TextToSpeech $textToSpeech, Storage $storage)
    {
        $this->mediaRepository = $mediaRepository;
        $this->textToSpeech = $textToSpeech;
        $this->storage = $storage;
    }

    public function createMp3(string $text): Media
    {
        /** @var Media $media */
        if ($media = $this->findOneByText($text)) {
            return $media;
        }

        $media = new Media();
        $media->setUuid(Uuid::uuid4());
        $media->setHash(hash('SHA256', $text));

        $filename = sprintf('%s.mp3', $media->getUuid());
        $mp3 = $this->textToSpeech->textToSpeech($text);
        $url = $this->storage->store($filename, $mp3);

        $media->setUrl($url);
        $media->setCreatedAt(new \DateTime());
        $media->setExpiresAt((new \DateTime())->add(new \DateInterval('P7D')));

        $this->mediaRepository->save($media);

        return $media;
    }

    public function clearExpired()
    {
        $this->mediaRepository->clearExpired();
    }

    private function findOneByText(string $text): ?Media
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