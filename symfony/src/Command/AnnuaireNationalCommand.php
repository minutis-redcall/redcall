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
        foreach (explode(';', getenv('ANNUAIRE_NATIONAL_MAIL_ALERTING')) as $to) {
            $this->emailManager->simple(
                $to,
                'Mise à jour de la base de données "Annuaire National"',
                sprintf("%d changement(s) ont été fait dans la base de données, vous trouverez le log ci-dessous.\n\n%s", LogService::getNbImpacts(), LogService::dump(true)),
                sprintf('<p>%d changement(s) ont été fait dans la base de données, vous trouverez le log ci-dessous.</p><br/><br/><pre>%s</pre>', LogService::getNbImpacts(), LogService::dump(true)),
                'fr'
            );
        }
    }
}