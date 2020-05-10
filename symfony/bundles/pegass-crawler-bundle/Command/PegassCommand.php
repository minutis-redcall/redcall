<?php

namespace Bundles\PegassCrawlerBundle\Command;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the N most outdated entries from Pegass
 * php bin/console pegass
 *
 * Force update a volunteer
 * php bin/console pegass --volunteer=00000342302R
 *
 * Force update a structure
 * php bin/console pegass --structure=889
 */
class PegassCommand extends Command
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
            ->setName('pegass')
            ->setDescription('Heat Pegass cache')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of entities to refresh', 3)
            ->addOption('from-cache', null, InputOption::VALUE_NONE, 'Process content from cache instead of fetching Pegass');

        foreach (Pegass::TYPES as $type) {
            $this->addOption($type, null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, sprintf('Force update an entity of type %s', $type));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('UTC');

        $score = 0;
        foreach (Pegass::TYPES as $type) {
            if ($input->getOption($type)) {
                $score++;
            }
        }

        if (!$score) {
            $this->pegassManager->heat(
                $input->getOption('limit'),
                $input->getOption('from-cache')
            );

            return 0;
        }

        // Force update the given entities
        foreach (Pegass::TYPES as $type) {
            foreach ($input->getOption($type) as $identifier) {
                $entity = $this->pegassManager->getEntity($type, $identifier, false);
                if ($entity) {
                    $this->pegassManager->updateEntity($entity, $input->getOption('from-cache'));
                    $output->writeln(sprintf('<info>Updated %s %s</info>', $entity->getType(), $entity->getIdentifier()));
                } else {
                    $output->writeln(sprintf('<error>%s %s not found</error>', lcfirst($type), $identifier));
                }
            }
        }

        return 0;
    }
}