<?php

namespace Bundles\PegassCrawlerBundle\Command;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Search all DLUS:
 * php bin/console pegass:search '/volunteer/nominations/libelleCourt[text()="DLUS"]' --type=volunteer
 *
 * Search all DLUS in Paris:
 * php bin/console pegass:search '/volunteer[user/structure/parent/id[.="80"]]/nominations/libelleCourt[.="DLUS"]'
 * --type=volunteer
 *
 * Search all *DLUS* in Paris:
 * php bin/console pegass:search
 * '/volunteer[user/structure/parent/id[contains(.,"80")]]/nominations/libelleCourt[contains(., "DLUS")]'
 * --type=volunteer
 */
class PegassSearchCommand extends Command
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(PegassManager $pegassManager, LoggerInterface $logger = null)
    {
        parent::__construct();

        $this->pegassManager = $pegassManager;
        $this->logger        = $logger ?: new NullLogger();
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
        $start       = microtime(true);
        $identifiers = [];

        $hashes = [];
        $count  = 0;
        foreach ($input->getOption('type') as $type) {
            $this->pegassManager->foreach($type, function (Pegass $pegass) use (
                $input,
                &$hashes,
                &$count,
                &
                $identifiers,
                &$values
            ) {
                $match = $pegass->xpath($input->getArgument('template'), $input->getOption('parameter'));
                if (!$match) {
                    return true;
                }

                $this->logger->debug(sprintf('Found %s %s', $pegass->getType(), $pegass->getIdentifier()));

                $identifiers[$pegass->getType()][] = [
                    'nivol'     => ltrim($pegass->getIdentifier(), '0'),
                    'firstname' => $pegass->evaluate('user.prenom'),
                    'lastname'  => $pegass->evaluate('user.nom'),
                    'values'    => implode(',', array_map('reset', array_values($match))),
                ];

                $count++;
                if ($input->getOption('limit') && $count == $input->getOption('limit')) {
                    return false;
                }

                return true;
            }, true);
        }

        foreach ($identifiers as $type => $list) {
            (new Table($output))
                ->setHeaders(['Nivol', 'First name', 'Last name', 'Value(s)'])
                ->setRows($list)
                ->render();
        }

        $end = microtime(true);

        $output->writeln(sprintf('Elapsed time: %.2f seconds', $end - $start));

        return 0;
    }
}