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
        $reports = $this->reportRepository->getCommunicationReportsBetween($from, $to, $minMessages);

        $structures = [];
        $costs      = [];

        foreach ($reports as $report) {
            /** @var Report $report */
            foreach ($report->getRepartitions() as $repartition) {
                $structure   = $repartition->getStructure();
                $structureId = $structure->getId();

                // Initializing
                if (!array_key_exists($structureId, $structures)) {
                    $costs[$structureId] = 0;
                    foreach ([Communication::TYPE_SMS, Communication::TYPE_CALL, Communication::TYPE_EMAIL] as $type) {
                        $structures[$structureId][$type] = [
                            'name'           => $structure->getName(),
                            'campaigns'      => [],
                            'type'           => $type,
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
                $ref                   = &$structures[$structureId][$report->getCommunication()->getType()];
                $ref['campaigns'][]    = $report->getCommunication()->getCampaign()->getId();
                $ref['communications'] += 1;
                $ref['messages']       += $repartition->getMessageCount();
                $ref['questions']      += $repartition->getQuestionCount();
                $ref['answers']        += $repartition->getAnswerCount();
                $ref['errors']         += $repartition->getErrorCount();
                foreach ($repartition->getCosts() as $currency => $amount) {
                    if (!isset($ref['costs'][$currency])) {
                        $ref['costs'][$currency] = 0;
                    }
                    $ref['costs'][$currency] += $amount;

                    // We mix all currencies (we currently support EUR and USD),
                    // but it's just for sorting results, so it doesn't matter.
                    $costs[$structureId] += $amount;
                }
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
}