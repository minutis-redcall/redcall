<?php

namespace App\Command;

use App\Manager\MailManager;
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

    public function __construct(VolunteerService $volunteerService, UserService $userService, MailManager $emailManager)
    {
        parent::__construct();

        $this->volunteerService = $volunteerService;
        $this->userService      = $userService;
        $this->emailManager     = $emailManager;
    }

    protected function configure()
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

        // Details
        $sections = [
            'errors'  => ['title' => 'Erreurs', 'color' => 'darkred'],
            'new'     => ['title' => 'Créations', 'color' => 'green'],
            'updated' => ['title' => 'Modifications', 'color' => 'orange'],
            'deleted' => ['title' => 'Suppressions', 'color' => 'red'],
        ];

        foreach ($sections as $key => $config) {
            if (empty($summary[$key])) {
                continue;
            }

            $html .= sprintf('<h2 style="color:%s">%s</h2>', $config['color'], $config['title']);
            $html .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
            $html .= '<tr><th style="padding: 8px; text-align: left;">Message</th><th style="padding: 8px; text-align: left;">Détails</th></tr>';

            foreach ($summary[$key] as $entry) {
                $details = '';
                foreach ($entry['parameters'] as $k => $v) {
                    $details .= sprintf('<strong>%s</strong>: %s<br/>', $k, $v);
                }

                $html .= sprintf(
                    '<tr><td style="padding: 8px;">%s</td><td style="padding: 8px;">%s</td></tr>',
                    $entry['message'],
                    $details
                );
            }
            $html .= '</table>';
        }

        // Full Log Dump (collapsed or at bottom)
        $html .= '<h2>Log complet technique</h2>';
        $html .= sprintf('<pre>%s</pre>', LogService::dump(true));

        // Text version (fallback)
        $text = strip_tags(str_replace(['<br/>', '</tr>', '</h1>', '</h2>'], "\n", $html));

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
}