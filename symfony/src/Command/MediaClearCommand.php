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

    /**
     * @param MediaManager $mediaManager
     */
    public function __construct(MediaManager $mediaManager)
    {
        parent::__construct();

        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('media:clear')
            ->setDescription('Clear expired medias');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mediaManager->clearExpired();

        return 0;
    }
}