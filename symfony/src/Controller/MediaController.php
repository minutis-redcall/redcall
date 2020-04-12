<?php

namespace App\Controller;


use App\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="media", name="media_")
 */
class MediaController
{
    /**
     * @Route("/{uuid}.mp3", name="play")
     */
    public function play(Media $media)
    {
        return new Response(stream_get_contents($media->getContent()), 200, [
            'Content-Type' => 'audio/mpeg',
        ]);
    }
}