<?php

namespace App\Manager;

use App\Repository\ReportRepository;
use Symfony\Component\Console\Output\OutputInterface;

class ReportManager
{
    /**
     * @var ReportRepository
     */
    private $reportCommunicationRepository;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(ReportRepository $reportCommunicationRepository,
        CommunicationManager $communicationManager)
    {
        $this->reportCommunicationRepository = $reportCommunicationRepository;
        $this->communicationManager          = $communicationManager;
    }

    public function createReports(OutputInterface $output)
    {
        $this->output = $output;

        $communicationIds = $this->communicationManager->findCommunicationIdsRequiringReports();

    }


}