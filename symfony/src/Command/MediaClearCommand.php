<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\MediaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MediaClearCommand extends BaseCommand
{
    /**
     * @var MediaManager
     */
    private $mediaManager;

    public function __construct(MediaManager $mediaManager)
    {
        parent::__construct();

        $this->mediaManager = $mediaManager;
    }

    protected function configure()
    {
        $this
            ->setName('media:clear')
            ->setDescription('Clear expired medias');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mediaManager->clearExpired();

        return 0;
    }
}