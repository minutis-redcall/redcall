<?php

namespace App\Command;

use App\Manager\VolunteerManager;
use App\Model\InstancesNationales\SheetsExtract;
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
    /**
     * @var VolunteerService
     */
    private $volunteerService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(VolunteerService $volunteerService,
        UserService $userService,
        VolunteerManager $volunteerManager)
    {
        parent::__construct();

        $this->volunteerService = $volunteerService;
        $this->userService      = $userService;
        $this->volunteerManager = $volunteerManager;
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('annuaire-national')
             ->setDescription('Importe les listes de l\'annuaire national');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $error = false;
        try {
            if (is_file('/tmp/annuaire.json')) {
                $volunteerExtract = SheetsExtract::fromArray(json_decode(file_get_contents('/tmp/annuaire.json'), true));
            } else {
                $volunteerExtract = $this->volunteerService->extractVolunteersFromGSheets();
                file_put_contents('/tmp/annuaire.json', json_encode($volunteerExtract->toArray()));
            }

            if (is_file('/tmp/droits.json')) {
                $userExtract = SheetsExtract::fromArray(json_decode(file_get_contents('/tmp/droits.json'), true));
            } else {
                $userExtract = $this->userService->extractUsersFromGSheets();
                file_put_contents('/tmp/droits.json', json_encode($userExtract->toArray()));
            }

            $volunteers = $this->volunteerService->extractVolunteers($volunteerExtract->getTab(VolunteerService::ANNUAIRE));
            $users      = $this->userService->extractUsers($userExtract);

            // TODO step 1, correlate "id" (first row in the Google Sheets) with the volunteer we have in our db
            // TODO step 2, correlate lists (from columns I to Z) with the volunteer lists we have in DB for "instances nationales"
            // TODO step 3, combine lists extracted from step 2 with volunteers extracted from step 1

        } catch (\Exception $exception) {
            $error = true;

            LogService::fail('An error occured during import.', [
                'exception' => $exception->getMessage(),
                'trace'     => $exception->getTraceAsString(),
            ]);
        } finally {
            LogService::dump();
        }

        return (int) !$error;
    }
}