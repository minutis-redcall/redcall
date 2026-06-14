<?php

namespace App\Command;

use App\Manager\MailManager;
use App\Provider\Storage\StorageProvider;
use App\Services\InstancesNationales\LogService;
use App\Services\InstancesNationales\UserService;
use App\Services\InstancesNationales\VolunteerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The French red cross national entities manage a few list of key people
 * (volunteers, staff, etc.) in Google Sheets. This command fetches these
 * lists and import them into the database.
 *
 * The command code is in French because the Google Sheets are in French.
 */
class AnnuaireNationalCommand extends Command
{
    const STRUCTURE_NAME = 'ANNUAIRE NATIONAL';

    /**
     * @var VolunteerService
     */
    private $volunteerService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var MailManager
     */
    private $emailManager;

    /**
     * @var StorageProvider
     */
    private $storageProvider;

    public function __construct(VolunteerService $volunteerService,
        UserService $userService,
        MailManager $emailManager,
        StorageProvider $storageProvider)
    {
        parent::__construct();

        $this->volunteerService = $volunteerService;
        $this->userService      = $userService;
        $this->emailManager     = $emailManager;
        $this->storageProvider  = $storageProvider;
    }

    protected function configure() : void
    {
        parent::configure();

        $this->setName('import:national')
             ->setDescription('Importe les listes de l\'annuaire national');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $error = false;
        try {
            $this->volunteerService->extractVolunteers();
            $this->userService->extractUsers();
        } catch (\Throwable $exception) {
            $error = true;

            LogService::fail('An error occurred during import.', [
                'exception' => $exception->getMessage(),
                'trace'     => $exception->getTraceAsString(),
            ], true);

        } finally {
            LogService::dump();

            if (LogService::isImpactful()) {
                $this->sendEmail();
            }
        }

        return (int) !$error;
    }

    private function sendEmail()
    {
        $summary = LogService::getSummary();
        $counts = [
            'new'     => count($summary['new']),
            'updated' => count($summary['updated']),
            'deleted' => count($summary['deleted']),
            'errors'  => count($summary['errors']),
        ];

        $subject = sprintf(
            'Mise à jour de l\'annuaire national : %d création(s), %d modification(s), %d suppression(s), %d erreur(s)',
            $counts['new'],
            $counts['updated'],
            $counts['deleted'],
            $counts['errors']
        );

        $reportUrl   = null;
        $reportError = null;
        try {
            $reportUrl = $this->storageProvider->store(
                sprintf('annuaire-national/rapport-%s.txt', date('Y-m-d-His')),
                // BOM so that editors without charset detection open the file as UTF-8
                "\xEF\xBB\xBF".$this->buildReport($summary, $counts),
                'text/plain; charset=utf-8'
            );
        } catch (\Throwable $exception) {
            $reportError = $exception->getMessage();
        }

        $html = sprintf('<h1>Rapport de mise à jour de l\'annuaire national</h1>');
        $html .= '<p>La synchronisation avec les Google Sheets a été effectuée.</p>';

        // Summary Table
        $html .= '<h2>Résumé</h2>';
        $html .= '<ul>';
        $html .= sprintf('<li><span style="color:green">●</span> Créations : <strong>%d</strong></li>', $counts['new']);
        $html .= sprintf('<li><span style="color:orange">●</span> Modifications : <strong>%d</strong></li>', $counts['updated']);
        $html .= sprintf('<li><span style="color:red">●</span> Suppressions : <strong>%d</strong></li>', $counts['deleted']);
        if ($counts['errors'] > 0) {
            $html .= sprintf('<li><span style="color:darkred">●</span> Erreurs : <strong>%d</strong></li>', $counts['errors']);
        }
        $html .= '</ul>';

        // Link to the detailed report (changes are too numerous to be inlined in the email)
        if ($reportUrl) {
            $html .= sprintf(
                '<p>Le détail des modifications est disponible dans <a href="%s">le rapport complet</a> (fichier texte, lien valable %d jours).</p>',
                $reportUrl,
                $this->storageProvider->getRetentionDays()
            );
        } else {
            $html .= sprintf(
                '<p style="color:darkred">Le rapport détaillé n\'a pas pu être déposé sur le stockage : %s</p>',
                $reportError
            );
        }

        // Text version (fallback)
        $text = strip_tags(str_replace(['<br/>', '</li>', '</p>', '</h1>', '</h2>'], "\n", $html));
        if ($reportUrl) {
            $text .= sprintf("\nRapport complet : %s\n", $reportUrl);
        }

        foreach (explode(';', getenv('ANNUAIRE_NATIONAL_MAIL_ALERTING')) as $to) {
            $this->emailManager->simple(
                $to,
                $subject,
                $text,
                $html,
                'fr'
            );
        }
    }

    private function buildReport(array $summary, array $counts) : string
    {
        $lines   = [];
        $lines[] = 'RAPPORT DE MISE À JOUR DE L\'ANNUAIRE NATIONAL';
        $lines[] = sprintf('Généré le %s', date('d/m/Y à H:i:s'));
        $lines[] = '';
        $lines[] = 'RÉSUMÉ';
        $lines[] = sprintf('- Créations : %d', $counts['new']);
        $lines[] = sprintf('- Modifications : %d', $counts['updated']);
        $lines[] = sprintf('- Suppressions : %d', $counts['deleted']);
        $lines[] = sprintf('- Erreurs : %d', $counts['errors']);

        $sections = [
            'errors'  => 'ERREURS',
            'new'     => 'CRÉATIONS',
            'updated' => 'MODIFICATIONS',
            'deleted' => 'SUPPRESSIONS',
        ];

        foreach ($sections as $key => $title) {
            if (empty($summary[$key])) {
                continue;
            }

            $lines[] = '';
            $lines[] = sprintf('======== %s (%d) ========', $title, count($summary[$key]));

            foreach ($summary[$key] as $entry) {
                $lines[] = sprintf('- %s', $entry['message']);
                foreach ($entry['parameters'] as $k => $v) {
                    $lines[] = sprintf('    %s: %s', $k, $v);
                }
            }
        }

        $lines[] = '';
        $lines[] = '======== LOG COMPLET TECHNIQUE ========';
        $lines[] = LogService::dump(true);

        return implode(PHP_EOL, $lines);
    }
}