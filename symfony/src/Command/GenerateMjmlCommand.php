<?php

namespace App\Command;

use App\Services\Mjml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Usage:
 * php bin/console generate:mjml templates/message/email.html.twig.mjml
 */
class GenerateMjmlCommand extends Command
{
    protected static $defaultName = 'generate:mjml';
    /**
     * @var Mjml
     */
    private $mjmlClient;
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @param Mjml $mjmlClient
     */
    public function __construct(Mjml $mjmlClient)
    {
        parent::__construct();

        $this->mjmlClient = $mjmlClient;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate the HTML template from an MJML file if it has changed')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the MJML file');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->io = new SymfonyStyle($input, $output);
        $mjmlPath = $input->getArgument('path');

        if (null === ($currentHash = $this->getCurrentHash($mjmlPath))) {
            return 1;
        }

        $olderHash = $this->getOlderHash($mjmlPath);
        if ($currentHash === $olderHash) {
            $this->io->comment(sprintf('%s is already up to date', $mjmlPath));

            return 0;
        }

        $this->io->text(sprintf('Generating Twig file for %s...', $mjmlPath));

        $twigCode = $this->mjmlClient->convert(
            file_get_contents($mjmlPath)
        );

        $twigPath = \substr($mjmlPath, 0, -5);
        file_put_contents($twigPath, $twigCode);

        $hashFile = sprintf('%s.sha1', $mjmlPath);
        file_put_contents($hashFile, $currentHash);

        $this->io->success(sprintf('Successfully converted %s -> %s', $mjmlPath, $twigPath));

        return 0;
    }

    private function getCurrentHash(string $mjmlPath) : ?string
    {
        if (!file_exists($mjmlPath) || !is_readable($mjmlPath)) {
            $this->io->error(sprintf('File %s not found or not readable', $mjmlPath));

            return null;
        }

        $currentHash = sha1(file_get_contents($mjmlPath));

        $this->io->text(sprintf('Current hash for %s: %s', $mjmlPath, $currentHash));

        return $currentHash;
    }

    private function getOlderHash(string $mjmlPath) : ?string
    {
        $hashPath = sprintf('%s.sha1', $mjmlPath);
        if (!is_file($hashPath) || !is_readable($hashPath)) {
            $this->io->text(sprintf('No hash file %s found', $hashPath));

            return null;
        }

        $olderHash = file_get_contents($hashPath);

        $this->io->text(sprintf('Older hash for %s: %s', $mjmlPath, $olderHash));

        return $olderHash;
    }
}
