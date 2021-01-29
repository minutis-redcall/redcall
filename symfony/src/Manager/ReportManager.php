<?php

namespace App\Manager;

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

            $report = $this->createReport($communication);

            $this->reportRepository->save($report);

            foreach ($report->getRepartitions() as $repartition) {
                $this->repartitionRepository->save($repartition);
            }

            $this->communicationManager->save($communication);

            $this->communicationManager->clearEntityManager();
        }
    }

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to) : array
    {
        return $this->reportRepository->getCommunicationReportsBetween($from, $to);
    }

    private function createReport(Communication $communication) : Report
    {
        $report     = $communication->getReport() ?? new Report();
        $hasChoices = $communication->getChoices()->count() > 0;

        $report->setCommunication($communication);
        $report->setType($communication->getType());

        foreach ($communication->getMessages() as $message) {
            $this->incrementCounters($message, $hasChoices, $report);
        }

        if ($report->getQuestionCount()) {
            $report->setAnswerRatio((int) ($report->getAnswerCount() * 100 / $report->getQuestionCount()));
        }

        $this->createRepartition($communication, $report);

        return $report;
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
            if ($hasChoices && $repartition->getQuestionCount()) {
                $repartition->setAnswerRatio($repartition->getAnswerCount() * 100 / $repartition->getQuestionCount());
            }

            if ($repartition->getMessageCount()) {
                $repartition->setRatio((int) ($repartition->getMessageCount() * 100 / $report->getMessageCount()));
            }

            if ($repartition->getQuestionCount()) {
                $repartition->setRatio((int) ($repartition->getQuestionCount() * 100 / $report->getQuestionCount()));
            }

            if ($repartition->getMessageCount() || $repartition->getQuestionCount()) {
                $report->addRepartition($repartition);
            }
        }
    }

    /**
     * @param Report|ReportRepartition $entity
     */
    private function incrementCounters(Message $message, bool $communicationHasChoices, $entity)
    {
        if ($communicationHasChoices) {
            $entity->setQuestionCount($entity->getQuestionCount() + 1);
            $entity->setAnswerCount($entity->getAnswerCount() + (int) ($message->getAnswers()->count() > 0));
        } else {
            $entity->setMessageCount($entity->getMessageCount() + 1);
        }

        $entity->setExchangeCount($entity->getExchangeCount() + $message->getAnswers()->count());

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
}