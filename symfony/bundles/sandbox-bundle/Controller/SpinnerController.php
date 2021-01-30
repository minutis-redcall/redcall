<?php

namespace Bundles\SandboxBundle\Controller;

use Bundles\SandboxBundle\Services\AnimGif;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SpinnerController extends AbstractController
{
    /**
     * @Route("/spinner", name="spinner")
     * @Template
     */
    public function index(Request $request)
    {
        $form = $this
            ->createFormBuilder(['splits' => 12])
            ->add('file', FileType::class, [
                'label' => false,
            ])
            ->add('splits', IntegerType::class, [
                'label' => 'sandbox.spinner.splits',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file   = $form->get('file')->getData();
            $splits = $form->get('splits')->getData();
            $images = $this->createSplits($file, $splits);
            $result = $this->createResult($file, $splits);
        }

        return [
            'form'   => $form->createView(),
            'images' => $images ?? null,
            'result' => $result ?? null,
        ];
    }

    private function createSplits(string $path, int $count) : array
    {
        $splits = [];

        $contents = file_get_contents($path);
        $step     = 100 / $count;

        for ($opacity = $step; $opacity < 100; $opacity += $step) {
            $image = $this->createImage($contents, $opacity);
            ob_start();
            imagegif($image);
            $bytes    = ob_get_clean();
            $splits[] = base64_encode($bytes);
        }

        return $splits;
    }

    private function createResult(string $path, int $count) : string
    {
        $images   = [];
        $contents = file_get_contents($path);
        $step     = 100 / $count;

        for ($opacity = $step; $opacity < 100; $opacity += $step) {
            $images[] = $this->createImage($contents, $opacity);
        }

        for ($i = count($images) - 1; $i != 0; $i--) {
            $images[] = $images[$i];
        }

        $anim = new AnimGif();
        $anim->create($images, 5);

        $bytes = $anim->get();

        return base64_encode($bytes);
    }

    private function createImage(string $contents, float $opacity)
    {
        $image = imagecreatefromstring($contents);
        imagealphablending($image, false);

        $color = imagecolorat($image, 0, 0);
        $r     = ($color >> 16) & 0xFF;
        $g     = ($color >> 8) & 0xFF;
        $b     = $color & 0xFF;

        $transp = imagecolorallocatealpha($image, $r, $g, $b, 127);
        imagecolortransparent($image, $transp);

        // On a PNG, the following code is enough:
        //
        //        imagesavealpha($image, true);
        //        $transparency = 1 - $opacity / 100;
        //        imagefilter($image, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * $transparency);

        // But on GIFs, transparency is a single color, so we'll mix the color with white instead.
        // White = opacity 0%
        // original color at pixel = opacity 100%

        // #e9ecef
        $mixer  = 0xe9ecef;
        $rMixer = ($mixer >> 16) & 0xFF;
        $gMixer = ($mixer >> 8) & 0xFF;
        $bMixer = $mixer & 0xFF;

        $width  = imagesx($image);
        $height = imagesy($image);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                if ($color == $transp) {
                    continue;
                }

                // Original color
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                // Mixed color

                $r = (($r * $opacity) / 100) + (($rMixer * (100 - $opacity)) / 100);
                $g = (($g * $opacity) / 100) + (($gMixer * (100 - $opacity)) / 100);
                $b = (($b * $opacity) / 100) + (($bMixer * (100 - $opacity)) / 100);

                $mixed = imagecolorallocate($image, $r, $g, $b);
                imagesetpixel($image, $x, $y, $mixed);
            }
        }

        return $image;
    }
}