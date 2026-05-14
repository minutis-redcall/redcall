<?php

namespace App\Command;

use App\Base\BaseCommand;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PortLegacyMigrationsCommand extends BaseCommand
{
    private const LEGACY_TABLE = 'migration_versions';
    private const NEW_TABLE    = 'doctrine_migration_versions';
    private const NAMESPACE    = 'DoctrineMigrations';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setName('migrations:port-legacy')
            ->setDescription(sprintf(
                'Port entries from the legacy %s table to %s with FQCN versions',
                self::LEGACY_TABLE,
                self::NEW_TABLE
            ))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be inserted without writing')
            ->addOption('drop-legacy', null, InputOption::VALUE_NONE, sprintf('Drop the %s table after a successful port', self::LEGACY_TABLE));
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::LEGACY_TABLE])) {
            $output->writeln(sprintf('<comment>Legacy table %s does not exist; nothing to do.</comment>', self::LEGACY_TABLE));

            return 0;
        }

        if (!$schemaManager->tablesExist([self::NEW_TABLE])) {
            $output->writeln(sprintf(
                '<error>Target table %s is missing. Run any doctrine:migrations command first to let DoctrineMigrationsBundle create it.</error>',
                self::NEW_TABLE
            ));

            return 1;
        }

        $legacyRows = $this->connection->fetchAllAssociative(
            sprintf('SELECT version, executed_at FROM %s ORDER BY version ASC', self::LEGACY_TABLE)
        );
        $existing   = array_flip(array_column(
            $this->connection->fetchAllAssociative(sprintf('SELECT version FROM %s', self::NEW_TABLE)),
            'version'
        ));

        $output->writeln(sprintf('<info>Legacy entries:</info> %d', count($legacyRows)));
        $output->writeln(sprintf('<info>Already in %s:</info> %d', self::NEW_TABLE, count($existing)));

        $diskVersions = $this->scanDiskVersions();

        $inserted = 0;
        $skipped  = 0;
        $missing  = [];

        foreach ($legacyRows as $row) {
            $fqcn = sprintf('%s\\Version%s', self::NAMESPACE, $row['version']);

            if (isset($existing[$fqcn])) {
                $skipped++;
                continue;
            }

            if (!isset($diskVersions[$row['version']])) {
                $missing[] = $row['version'];
            }

            $output->writeln(sprintf('  + %s', $fqcn), OutputInterface::VERBOSITY_VERBOSE);

            if (!$input->getOption('dry-run')) {
                $this->connection->insert(self::NEW_TABLE, [
                    'version'        => $fqcn,
                    'executed_at'    => $row['executed_at'],
                    'execution_time' => null,
                ]);
            }
            $inserted++;
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Inserted:</info> %d', $inserted));
        $output->writeln(sprintf('<info>Skipped (already present):</info> %d', $skipped));

        if ($missing) {
            $output->writeln(sprintf('<comment>Warning: %d legacy entries have no matching file in src/Migrations:</comment>', count($missing)));
            foreach ($missing as $version) {
                $output->writeln(sprintf('  - %s', $version));
            }
        }

        if ($input->getOption('dry-run')) {
            $output->writeln('<comment>--dry-run: no changes written.</comment>');

            return 0;
        }

        if ($input->getOption('drop-legacy')) {
            $output->writeln(sprintf('<info>Dropping legacy table %s...</info>', self::LEGACY_TABLE));
            $this->connection->executeStatement(sprintf('DROP TABLE %s', self::LEGACY_TABLE));
        }

        return 0;
    }

    /**
     * @return array<string, true> map of date-style versions present on disk
     */
    private function scanDiskVersions() : array
    {
        $dir   = __DIR__ . '/../Migrations';
        $found = [];

        foreach (glob($dir . '/Version*.php') as $path) {
            if (preg_match('/Version(\d{14})\.php$/', $path, $m)) {
                $found[$m[1]] = true;
            }
        }

        return $found;
    }
}
