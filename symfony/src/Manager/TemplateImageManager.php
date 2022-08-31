<?php

namespace App\Manager;

use App\Entity\Template;
use App\Entity\TemplateImage;
use App\Repository\TemplateImageRepository;

class TemplateImageManager
{
    /**
     * @var TemplateImageRepository
     */
    protected $templateImageRepository;

    public function __construct(TemplateImageRepository $templateImageRepository)
    {
        $this->templateImageRepository = $templateImageRepository;
    }

    public function handleImages(Template $template, string $body) : string
    {
        foreach ($template->getImages() as $image) {
            $template->removeImage($image);
        }

        $matches = [];
        preg_match_all('|\<img src=\"data\:image\/(.[^\;]+)\;base64\,(.[^\"]+)\"\>|', $body, $matches);

        foreach (array_keys($matches[0]) as $index) {
            $binary = base64_decode($matches[2][$index]);
            if (!$binary) {
                continue;
            }

            if (!$gd = @imagecreatefromstring($binary)) {
                continue;
            }
            imagesavealpha($gd, true);
            ob_start();
            imagepng($gd);
            $clean = base64_encode(ob_get_clean());

            $image = new TemplateImage();
            $image->setContent($clean);

            $this->templateImageRepository->add($image, false);
            $template->addImage($image);

            $body = str_replace(
                $matches[0][$index],
                sprintf('{image:%s}', $image->getUuid()),
                $body
            );
        }

        return $body;
    }
}