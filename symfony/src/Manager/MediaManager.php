<?php

namespace App\Manager;

use App\Entity\Media;
use App\Repository\MediaRepository;
use App\Services\TextToSpeech;

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
     * @param MediaRepository $mediaRepository
     * @param TextToSpeech    $textToSpeech
     */
    public function __construct(MediaRepository $mediaRepository, TextToSpeech $textToSpeech)
    {
        $this->mediaRepository = $mediaRepository;
        $this->textToSpeech = $textToSpeech;
    }

    public function findUuidByText(string $text): ?string
    {
        /** @var Media|null $media */
        $media = $this->mediaRepository->findOneByHash(
            hash('SHA256', $text)
        );

        if (!$media) {
            return null;
        }

        return $media->getUuid();
    }

    public function createMp3(string $text): string
    {
        /** @var Media $media */
        if ($media = $this->findUuidByText($text)) {
            return $media->getUuid();
        }

        $media = new Media();
        $media->setUuid(Uuid::uuid4());
        $media->setHash(hash('SHA256', $text));
        $media->setContent(
            $this->textToSpeech->textToSpeech($text)
        );

        $media->setCreatedAt(new \DateTime());
        $media->setExpiresAt((new \DateTime())->add(new \DateInterval('P7D')));

        $this->mediaManager->save($media);

        return $media->getUuid();
    }
}