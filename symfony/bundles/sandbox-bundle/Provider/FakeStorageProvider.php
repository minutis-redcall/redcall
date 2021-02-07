<?php

namespace Bundles\SandboxBundle\Provider;

use App\Provider\Storage\StorageProvider;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class FakeStorageProvider implements StorageProvider
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(KernelInterface $kernel, RouterInterface $router)
    {
        $this->kernel = $kernel;
        $this->router = $router;
    }

    static public function getPath(string $directory, string $filename) : string
    {
        $directory = sprintf('%s/media', $directory);

        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $filename = str_replace(['/', '\\', '..'], '-', $filename);

        return sprintf('%s/%s', $directory, $filename);
    }

    public function store(string $filename, string $content) : string
    {
        $dir = $this->kernel->getCacheDir();

        $path = self::getPath($dir, $filename);

        file_put_contents($path, $content);

        $path = $this->router->generate('sandbox_fake_storage_access', [
            'filename' => basename($path),
        ]);

        return sprintf('%s%s', getenv('WEBSITE_URL'), $path);
    }

    public function getRetentionDays() : int
    {
        return 42;
    }
}