<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\Pegass;
use App\Manager\PegassManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command help analysis data from Pegass
 *
 * Get distinct communication ids
 * php bin/console pegass:search --type volunteer --distinct contact[0].moyenComId
 *
 * Get distinct trainings
 * php bin/console pegass:search --type volunteer  trainings[0].formation.code trainings[0].formation.libelle --distinct
 *
 * Get specific data from a volunteer (root paths: user, infos, contact, actions, skills, trainings, nominations)
 * php bin/console pegass:search --type volunteer --filter tiemblo contact
 *
 * Get specific data from a structure (root paths: responsible, structure, volunteers)
 * php bin/console pegass:search --type structure --filter "paris 1er"  structure.libelle responsible.responsableId
 */
class PegassSearchCommand extends BaseCommand
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
            ->setDescription('Extract data from Pegass database for easier analysis')
            ->addArgument('expressions', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Json path expression(s) to seek for')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, sprintf('Pegass data type (%s)', implode(', ', Pegass::TYPES)), Pegass::TYPES)
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only fetch results matching any of these filters', [])
            ->addOption('distinct', 'd', InputOption::VALUE_NONE, 'Only render distinct Pegass values')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit table to N results');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaderTitle('Pegass Search');
        $table->setHeaders(array_merge(['Type', 'Identifier'], $input->getArgument('expressions') ?: ['content']));

        $hashes = [];
        $count  = 0;
        foreach ($input->getOption('type') as $type) {
            $this->pegassManager->foreach($type, function (Pegass $pegass) use ($input, &$hashes, $table, &$count) {

                // Filtering results by their content
                if ($input->getOption('filter')) {
                    $matched = false;
                    foreach ($input->getOption('filter') as $filter) {
                        if (stripos(json_encode($pegass->getContent()), $filter)) {
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        return;
                    }
                }

                // Extracting data from JSON path expressions
                if (!$input->getArgument('expressions')) {
                    $matches[] = json_encode($pegass->getContent(), JSON_PRETTY_PRINT);
                } else {
                    $matches = [];
                    foreach ($input->getArgument('expressions') as $expression) {
                        $match = $pegass->evaluate($expression);

                        if ($match && !is_scalar($match)) {
                            $match = json_encode($match, JSON_PRETTY_PRINT);
                        }

                        $matches[] = $match;
                    }
                    if (!array_filter($matches)) {
                        return;
                    }
                }

                // Removing duplicates if necessary
                if ($input->getOption('distinct')) {
                    $hash = sha1(json_encode($matches));

                    if (in_array($hash, $hashes)) {
                        return;
                    }

                    $hashes[] = $hash;
                }

                $table->addRow(array_merge([$pegass->getType(), $pegass->getIdentifier()], $matches));
                $count++;

                if ($input->getOption('limit') && $count == $input->getOption('limit')) {
                    return false;
                }
            }, false);
        }

        $table->render();
    }
}