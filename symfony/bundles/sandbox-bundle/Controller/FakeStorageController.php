<?php

namespace Bundles\SandboxBundle\Controller;

use Bundles\SandboxBundle\Provider\FakeStorageProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/fake-storage", name="fake_storage_")
 */
class FakeStorageController extends AbstractController
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Route("/{filename}", name="access")
     */
    public function access(string $filename)
    {
        $path = FakeStorageProvider::getPath($this->kernel->getCacheDir(), $filename);

        if (!is_file($path)) {
            throw $this->createNotFoundException();
        }

        return new Response(
            file_get_contents($path),
            Response::HTTP_OK,
            [
                'Content-Type' => mime_content_type($path),
            ]
        );
    }

}