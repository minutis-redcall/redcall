<?php

namespace App\Command;

use App\Manager\VolunteerManager;
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
            $this->volunteerService->extractVolunteers();
            $this->userService->extractUsers();
        } catch (\Throwable $exception) {
            $error = true;

            LogService::fail('An error occurred during import.', [
                'exception' => $exception->getMessage(),
                'trace'     => $exception->getTraceAsString(),
            ]);
        } finally {
            LogService::dump();
        }

        return (int) !$error;
    }
}