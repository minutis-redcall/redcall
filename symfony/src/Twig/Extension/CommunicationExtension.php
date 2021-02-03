<?php

namespace App\Twig\Extension;

use App\Entity\Communication;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CommunicationExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('format_email', [$this, 'formatEmail'], [
                'needs_environment' => true,
                'is_safe'           => ['html'],
            ]),
        ];
    }

    /**
     * I first tried to use something like this in Communication:
     *
     * public function getFormattedBody() : ?string
     * {
     *     $body = $this->body;
     *     foreach ($this->images as $image) {
     *         $body = str_replace(
     *             sprintf('{image:%s}', $image->getUuid()),
     *             sprintf('<img class="img-fluid" src="%s"/>', $image->getUrl()),
     *             $body
     *         );
     *     }
     *
     *     return $body;
     * }
     *
     * But in emails, we cannot inject <img> like that, we should use responsive images support
     * provided by mjml.
     */
    public function formatEmail(Environment $environment, Communication $communication) : ?string
    {
        $body = $communication->getBody();

        if (!$body) {
            return null;
        }

        foreach ($communication->getImages() as $image) {
            $img = $environment->render('message/image.html.twig', [
                'url' => $image->getUrl(),
            ]);

            // In order to generate the mjml representation of an image, we need to generate
            // an entire email, but only the image section is interesting for us.
            $img = substr($img, strpos($img, '<!-- image:begin -->') + 20);
            $img = substr($img, 0, strpos($img, '<!-- image:end -->'));

            $body = str_replace(
                sprintf('{image:%s}', $image->getUuid()),
                $img,
                $body
            );
        }

        return $body;
    }
}