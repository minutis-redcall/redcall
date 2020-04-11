<?php

namespace App\Controller;


use App\Entity\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="media", name="media_")
 */
class MediaController
{
    /**
     * @Route("/{uuid}", name="play")
     */
    public function play(Media $media)
    {
        return new StreamedResponse(function() use ($media) {
            $handle = fopen($media->getContent(), 'r');
            while (!feof($handle)) {
                $buffer = fread($handle, 1024);
                echo $buffer;
                ob_flush();
                flush();
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'audio/mpeg',
        ]);
    }
}