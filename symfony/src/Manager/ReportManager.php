<?php

namespace App\Manager;

use App\Entity\AbstractReport;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Report;
use App\Entity\ReportRepartition;
use App\Repository\ReportRepartitionRepository;
use App\Repository\ReportRepository;
use Symfony\Component\Console\Output\OutputInterface;

class ReportManager
{
    /**
     * @var ReportRepository
     */
    private $reportRepository;

    /**
     * @var ReportRepartitionRepository
     */
    private $repartitionRepository;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(ReportRepository $reportRepository,
        ReportRepartitionRepository $repartitionRepository,
        CommunicationManager $communicationManager,
        StructureManager $structureManager)
    {
        $this->reportRepository      = $reportRepository;
        $this->repartitionRepository = $repartitionRepository;
        $this->communicationManager  = $communicationManager;
        $this->structureManager      = $structureManager;
    }

    public function createReports(OutputInterface $output)
    {
        $communicationIds = $this->communicationManager->findCommunicationIdsRequiringReports();

        foreach ($communicationIds as $communicationId) {
            $communication = $this->communicationManager->find($communicationId);

            $output->writeln(sprintf('Handling communication #%d: %s', $communicationId, $communication->getCampaign()->getLabel()));

            $this->createReport($communication);

            $this->communicationManager->clearEntityManager();
        }
    }

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to) : array
    {
        return $this->reportRepository->getCommunicationReportsBetween($from, $to);
    }

    public function createReport(Communication $communication) : Report
    {
        $report     = $communication->getReport() ?? new Report();
        $hasChoices = $communication->getChoices()->count() > 0;

        $report->setCommunication($communication);
        $report->setType($communication->getType());

        foreach ($communication->getMessages() as $message) {
            $this->incrementCounters($message, $hasChoices, $report);
        }

        $this->createRepartition($communication, $report);

        $this->saveCommunicationReport($communication);

        return $report;
    }

    public function createStructureReport(\DateTime $from, \DateTime $to, int $minMessages)
    {
        $rawData = $this->reportRepository->getStructureReportData($from, $to, $minMessages);

        $structures = [];
        $costs      = [];

        foreach ($rawData as $row) {
            $structureId = (int) $row['structure_id'];
            $type        = $row['communication_type'];

            // Initializing
            if (!array_key_exists($structureId, $structures)) {
                $costs[$structureId] = 0;
                foreach ([Communication::TYPE_SMS, Communication::TYPE_CALL, Communication::TYPE_EMAIL] as $commType) {
                    $structures[$structureId][$commType] = [
                        'name'           => $row['structure_name'],
                        'campaigns'      => [],
                        'type'           => $commType,
                        'communications' => 0,
                        'messages'       => 0,
                        'questions'      => 0,
                        'answers'        => 0,
                        'errors'         => 0,
                        'costs'          => [],
                    ];
                }
            }

            // Filling
            $ref                   = &$structures[$structureId][$type];
            $ref['campaigns'][]    = (int) $row['campaign_id'];
            $ref['communications'] += 1;
            $ref['messages']       += (int) $row['messages'];
            $ref['questions']      += (int) $row['questions'];
            $ref['answers']        += (int) $row['answers'];
            $ref['errors']         += (int) $row['errors'];

            $rowCosts = json_decode($row['costs_json'] ?? '[]', true) ?: [];
            foreach ($rowCosts as $currency => $amount) {
                if (!isset($ref['costs'][$currency])) {
                    $ref['costs'][$currency] = 0;
                }
                $ref['costs'][$currency] += $amount;

                // We mix all currencies (we currently support EUR and USD),
                // but it's just for sorting results, so it doesn't matter.
                $costs[$structureId] += $amount;
            }
        }

        // Fixing campaign counts
        foreach ($structures as $structureId => $types) {
            foreach ($types as $type => $data) {
                $ref              = &$structures[$structureId][$type];
                $ref['campaigns'] = count(array_unique($ref['campaigns']));
            }
        }

        // Sorting by descending cost
        uksort($structures, function ($a, $b) use (&$costs) {
            return $costs[$a] <=> $costs[$b];
        });

        return $structures;
    }

    private function createRepartition(Communication $communication, Report $report)
    {
        $hasChoices = $communication->getChoices()->count() > 0;

        // In order to calculate the right volunteers repartition, we fetch all triggered structures
        // and order the results by the descendant number of volunteers per structures.
        $structures   = [];
        $repartitions = [];
        foreach ($this->communicationManager->getCommunicationStructures($communication) as $structureId) {
            $structure                = $this->structureManager->find($structureId);
            $structures[$structureId] = $structure;

            $repartition = new ReportRepartition();
            $repartition->setStructure($structure);
            $repartitions[$structureId] = $repartition;
        }

        // Then, for every messages, we find in which structures volunteer was triggered. The structure list
        // is ordered in a way that we are sure to bill the right structure (because there are good chances
        // the triggering structure is the one that has the most volunteers triggered)
        foreach ($communication->getMessages() as $message) {
            foreach ($structures as $structure) {
                if (!isset($structures[$structure->getId()])) {
                    continue;
                }

                if ($structure->getVolunteers()->contains($message->getVolunteer())) {
                    /** @var ReportRepartition $repartition */
                    $repartition = $repartitions[$structure->getId()];
                    $this->incrementCounters($message, $hasChoices, $repartition);
                    break;
                }
            }
        }

        // We finally create question/answer ratio, and proportion of messages per structure
        // among all messages sent in the trigger
        $report->getRepartitions()->clear();
        foreach ($repartitions as $structureId => $repartition) {
            if ($repartition->getMessageCount()) {
                $repartition->setRatio($repartition->getMessageCount() * 100 / $report->getMessageCount());
            }

            if ($repartition->getQuestionCount()) {
                $repartition->setRatio($repartition->getQuestionCount() * 100 / $report->getQuestionCount());
            }

            if ($repartition->getMessageCount() || $repartition->getQuestionCount()) {
                $report->addRepartition($repartition);
            }
        }
    }

    private function incrementCounters(Message $message, bool $communicationHasChoices, AbstractReport $entity)
    {
        if ($communicationHasChoices) {
            $entity->setQuestionCount($entity->getQuestionCount() + 1);
            $entity->setAnswerCount($entity->getAnswerCount() + (int) ($message->getAnswers()->count() > 0));
        } else {
            $entity->setMessageCount($entity->getMessageCount() + 1);
        }

        $entity->setExchangeCount($entity->getExchangeCount() + $message->getAnswers()->count());

        if ($message->getError()) {
            $entity->setErrorCount($entity->getErrorCount() + 1);
        }

        $entity->setCosts(
            $this->calculateMessageCosts($message, $entity->getCosts())
        );
    }

    private function calculateMessageCosts(Message $message, array $costs) : array
    {
        foreach ($message->getCosts() as $cost) {
            if (!isset($costs[$cost->getCurrency()])) {
                $costs[$cost->getCurrency()] = 0;
            }
            $costs[$cost->getCurrency()] += $cost->getPrice();
        }

        return $costs;
    }

    private function saveCommunicationReport(Communication $communication)
    {
        $report = $communication->getReport();

        $this->reportRepository->save($report);

        foreach ($report->getRepartitions() as $repartition) {
            $this->repartitionRepository->save($repartition);
        }

        $this->communicationManager->save($communication);
    }

    /**
     * Creates a detailed cost report for specific structures within a date range.
     * Uses native SQL for performance. Returns campaign-level breakdown with costs per structure.
     */
    public function createUserStructuresCostsReport(array $structureIds, \DateTime $from, \DateTime $to) : array
    {
        if (empty($structureIds)) {
            return [];
        }

        $rawData = $this->reportRepository->getCostsReportByStructures($structureIds, $from, $to);

        $structureReports = [];

        foreach ($rawData as $row) {
            $structureId     = (int) $row['structure_id'];
            $campaignId      = (int) $row['campaign_id'];
            $communicationId = (int) $row['communication_id'];

            // Initialize structure entry
            if (!isset($structureReports[$structureId])) {
                $structureReports[$structureId] = [
                    'name'       => $row['structure_name'],
                    'campaigns'  => [],
                    'totalCosts' => [],
                ];
            }

            // Initialize campaign entry
            if (!isset($structureReports[$structureId]['campaigns'][$campaignId])) {
                $structureReports[$structureId]['campaigns'][$campaignId] = [
                    'id'               => $campaignId,
                    'label'            => $row['campaign_label'],
                    'date'             => new \DateTime($row['campaign_date']),
                    'type'             => $row['communication_type'],
                    'communicationIds' => [], // Track unique communication IDs
                    'messages'         => 0,
                    'questions'        => 0,
                    'answers'          => 0,
                    'errors'           => 0,
                    'costs'            => [],
                ];
            }

            $campaignRef = &$structureReports[$structureId]['campaigns'][$campaignId];

            // Track unique communications
            if (!in_array($communicationId, $campaignRef['communicationIds'])) {
                $campaignRef['communicationIds'][] = $communicationId;
            }

            $campaignRef['messages']  += (int) $row['messages'];
            $campaignRef['questions'] += (int) $row['questions'];
            $campaignRef['answers']   += (int) $row['answers'];
            $campaignRef['errors']    += (int) $row['errors'];

            // Parse and aggregate costs from JSON
            $costs = json_decode($row['costs_json'] ?? '[]', true) ?: [];
            foreach ($costs as $currency => $amount) {
                if (!isset($campaignRef['costs'][$currency])) {
                    $campaignRef['costs'][$currency] = 0;
                }
                $campaignRef['costs'][$currency] += $amount;

                // Also aggregate to structure totals
                if (!isset($structureReports[$structureId]['totalCosts'][$currency])) {
                    $structureReports[$structureId]['totalCosts'][$currency] = 0;
                }
                $structureReports[$structureId]['totalCosts'][$currency] += $amount;
            }
        }

        // Convert communicationIds arrays to counts
        foreach ($structureReports as $structureId => &$structureData) {
            foreach ($structureData['campaigns'] as $campaignId => &$campaignData) {
                $campaignData['communications'] = count($campaignData['communicationIds']);
                unset($campaignData['communicationIds']); // Remove the tracking array
            }
        }

        // Sort campaigns by date within each structure (most recent first)
        foreach ($structureReports as $structureId => &$structureData) {
            uasort($structureData['campaigns'], function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });
        }

        return $structureReports;
    }

    /**
     * Creates monthly cost totals for specific structures over the last N months.
     * Uses native SQL for performance.
     */
    public function createUserStructuresMonthlyTotals(array $structureIds, int $months = 12) : array
    {
        if (empty($structureIds)) {
            return [];
        }

        // Calculate date range for all months
        $now  = new \DateTime();
        $to   = (clone $now)->modify('last day of previous month')->setTime(23, 59, 59);
        $from = (clone $now)->modify("-{$months} months")->modify('first day of this month')->setTime(0, 0, 0);

        $rawData = $this->reportRepository->getMonthlyTotalsByStructures($structureIds, $from, $to);

        // Initialize all months first
        $monthlyTotals = [];
        for ($i = 1; $i <= $months; $i++) {
            $monthStart               = (clone $now)->modify("-{$i} months")->modify('first day of this month')->setTime(0, 0, 0);
            $monthKey                 = $monthStart->format('Y-m');
            $monthlyTotals[$monthKey] = [
                'label'       => $monthStart->format('F Y'),
                'from'        => $monthStart,
                'to'          => (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59),
                'structures'  => [],
                'campaignIds' => [], // Track unique campaign IDs
                'totalCosts'  => [],
            ];
        }

        // Aggregate raw data
        foreach ($rawData as $row) {
            $monthKey    = $row['month_key'];
            $structureId = (int) $row['structure_id'];
            $campaignId  = (int) $row['campaign_id'];

            if (!isset($monthlyTotals[$monthKey])) {
                continue; // Skip if outside our range
            }

            // Track unique campaigns
            if (!in_array($campaignId, $monthlyTotals[$monthKey]['campaignIds'])) {
                $monthlyTotals[$monthKey]['campaignIds'][] = $campaignId;
            }

            // Initialize structure in this month
            if (!isset($monthlyTotals[$monthKey]['structures'][$structureId])) {
                $monthlyTotals[$monthKey]['structures'][$structureId] = [
                    'name'  => $row['structure_name'],
                    'costs' => [],
                ];
            }

            // Parse and aggregate costs from JSON
            $costs = json_decode($row['costs_json'] ?? '[]', true) ?: [];
            foreach ($costs as $currency => $amount) {
                // Structure-level costs for this month
                if (!isset($monthlyTotals[$monthKey]['structures'][$structureId]['costs'][$currency])) {
                    $monthlyTotals[$monthKey]['structures'][$structureId]['costs'][$currency] = 0;
                }
                $monthlyTotals[$monthKey]['structures'][$structureId]['costs'][$currency] += $amount;

                // Total costs for this month
                if (!isset($monthlyTotals[$monthKey]['totalCosts'][$currency])) {
                    $monthlyTotals[$monthKey]['totalCosts'][$currency] = 0;
                }
                $monthlyTotals[$monthKey]['totalCosts'][$currency] += $amount;
            }
        }

        // Convert campaignIds arrays to counts
        foreach ($monthlyTotals as $monthKey => &$monthData) {
            $monthData['campaigns'] = count($monthData['campaignIds']);
            unset($monthData['campaignIds']);
        }

        return $monthlyTotals;
    }
}