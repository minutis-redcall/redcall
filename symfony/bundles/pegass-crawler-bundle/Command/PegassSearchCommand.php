<?php

namespace Bundles\PegassCrawlerBundle\Command;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PegassSearchCommand extends Command
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    public function __construct(PegassManager $pegassManager)
    {
        parent::__construct();

        $this->pegassManager = $pegassManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass:search')
            ->setDescription('Extract identifiers from Pegass database using an Xpath expression')
            ->addArgument('template', InputArgument::REQUIRED, 'Xpath expression to match with')
            ->addOption('parameter', 'p', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Parameters to escape')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, sprintf('Pegass data type (%s)', implode(', ', Pegass::TYPES)), Pegass::TYPES)
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit to N results');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifiers = [];

        $hashes = [];
        $count  = 0;
        foreach ($input->getOption('type') as $type) {
            $this->pegassManager->foreach($type, function (Pegass $pegass) use ($input, &$hashes, &$count, &$identifiers) {
                $match = $pegass->xpath($input->getArgument('template'), $input->getOption('parameter'));
                if (!$match) {
                    return;
                }

                $identifiers[$pegass->getType()][] = $pegass->getIdentifier();

                $count++;
                if ($input->getOption('limit') && $count == $input->getOption('limit')) {
                    return false;
                }
            }, true);
        }

        foreach ($identifiers as $type => $list) {
            echo $type, ': ', implode(',', $list), PHP_EOL;
        }
    }
}