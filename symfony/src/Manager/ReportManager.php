<?php

namespace App\Manager;

use App\Entity\Communication;
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

            if (count($report->getRepartitions())) {
                $this->reportRepository->save($report);

                foreach ($report->getRepartitions() as $repartition) {
                    $this->repartitionRepository->save($repartition);
                }

                $this->communicationManager->save($communication);
            }

            $this->communicationManager->clearEntityManager();
        }
    }

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to) : array
    {
        return $this->reportRepository->getCommunicationReportsBetween($from, $to);
    }

    private function createReport(Communication $communication) : Report
    {
        $report = $communication->getReport() ?? new Report();

        $report->setCommunication($communication);
        $report->setType($communication->getType());
        $report->setChoiceCount(count($communication->getChoices()));
        $report->setMessageCount(count($communication->getMessages()));

        $answerCount = 0;
        $bounceCount = 0;
        $costs       = [];
        foreach ($communication->getMessages() as $message) {
            $answerCount += count($message->getAnswers()) > 0;
            $bounceCount += count($message->getAnswers());
            foreach ($message->getCosts() as $cost) {
                if (!isset($costs[$cost->getCurrency()])) {
                    $costs[$cost->getCurrency()] = 0;
                }
                $costs[$cost->getCurrency()] += $cost->getPrice();
            }
        }
        $report->setAnswerCount($answerCount);
        $report->setBounceCount($bounceCount);
        $report->setCost($costs);

        if ($report->getChoiceCount() && $report->getMessageCount()) {
            $report->setAnswerRatio((int) ($answerCount * 100 / $report->getMessageCount()));
        }

        $this->createRepartition($communication, $report);

        return $report;
    }

    private function createRepartition(Communication $communication, Report $report)
    {
        // In order to calculate the right volunteers repartition, we fetch all triggered structures
        // and order the results by the descendant number of volunteers per structures.
        $structures  = [];
        $repartition = [];
        foreach ($this->communicationManager->getCommunicationStructures($communication) as $structureId) {
            // Structure may have been removed from Pegass
            $structures[$structureId] = $this->structureManager->find($structureId);;
            $repartition[$structureId] = [
                'messages' => 0,
                'answers'  => 0,
                'bounces'  => 0,
            ];
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
                    // This is a simple way to calculate the ratio of triggered volunteers, but this is not
                    // fully true. Costs are not the same according to the volunteer phone numbers, and for
                    // very precise results, we should keep a relation between the structure and the
                    // $message->getCost() here.
                    $repartition[$structure->getId()]['messages'] += 1;
                    $repartition[$structure->getId()]['answers']  += (count($message->getAnswers()) > 0);
                    $repartition[$structure->getId()]['bounces']  += count($message->getAnswers());
                    break;
                }
            }
        }

        $report->getRepartitions()->clear();
        foreach ($repartition as $structureId => $counts) {
            if (!$counts['messages']) {
                continue;
            }

            $entity = new ReportRepartition();
            $entity->setStructure($structures[$structureId]);
            $entity->setMessageCount($counts['messages']);
            $entity->setAnswerCount($counts['answers']);
            $entity->setBounceCount($counts['bounces']);
            if (count($communication->getChoices()) && $counts['messages'] > 0) {
                $entity->setAnswerRatio($counts['answers'] * 100 / $counts['messages']);
            }

            $entity->setRatio(0);
            if ($report->getMessageCount()) {
                $entity->setRatio((int) ($entity->getMessageCount() * 100 / $report->getMessageCount()));
            }

            if ($entity->getRatio() > 0) {
                $report->addRepartition($entity);
            }
        }
    }
}