<?php

namespace App\Controller;

/**
 * @Route(path="media", name="media_")
 */
class MediaController
{
    /**
     * @Route("/{uuid}", name="play")
     */
    public function play()
    {
        return new Response();
    }

    // create Mp3Response

}