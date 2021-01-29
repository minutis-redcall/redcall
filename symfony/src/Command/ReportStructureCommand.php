<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\Communication;
use App\Entity\Report;
use App\Manager\ReportManager;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportStructureCommand extends BaseCommand
{
    /**
     * @var ReportManager
     */
    private $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        parent::__construct();

        $this->reportManager = $reportManager;
    }

    protected function configure()
    {
        $this
            ->setName('report:structure')
            ->addArgument('from', InputArgument::OPTIONAL, 'Get summaries ', '2020-01-01')
            ->addArgument('to', InputArgument::OPTIONAL, 'Date to', '2021-01-01')
            ->addOption('csv', null, InputOption::VALUE_NONE, 'Output as CSV')
            ->setDescription('Create a report');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reports = $this->reportManager->getCommunicationReportsBetween(
            new \DateTime($input->getArgument('from')),
            new \DateTime($input->getArgument('to'))
        );

        $structures = [];
        foreach ($reports as $report) {
            /** @var Report $report */
            foreach ($report->getRepartitions() as $repartition) {
                $structureId = $repartition->getStructure()->getId();

                if (!array_key_exists($structureId, $structures)) {
                    foreach ([Communication::TYPE_SMS, Communication::TYPE_CALL, Communication::TYPE_EMAIL] as $type) {
                        $structures[$structureId][$type] = [
                            $k1 = '#'                                        => $structureId,
                            $k2 = 'Nom'                                      => $repartition->getStructure()->getName(),
                            'Type'                                           => $type,
                            $k11 = 'Nombre de campagnes'                     => [],
                            $k10 = 'Nombre de communications'                => 0,
                            $k3 = 'Nombre de messages sans reponses'         => 0,
                            $k9 = 'Nombre de messages demandant une reponse' => 0,
                            $k4 = 'Nombre de réponses'                       => 0,
                            $k5 = 'Nombre de rebonds'                        => 0,
                            $k6 = 'Ratio de messages/réponses'               => 0,
                            $k7 = 'Dépenses EUR'                             => 0,
                            $k8 = 'Dépenses USD'                             => 0,
                        ];
                    }
                }

                $ref = &$structures[$structureId][$report->getCommunication()->getType()];
                if (count($report->getCommunication()->getChoices())) {
                    $ref[$k9] += $repartition->getMessageCount();
                } else {
                    $ref[$k3] += $repartition->getMessageCount();
                }
                $ref[$k4]    += $repartition->getAnswerCount();
                $ref[$k5]    += $repartition->getBounceCount();
                $ref[$k10]   += 1;
                $ref[$k11][] = $report->getCommunication()->getCampaign()->getId();
                $cost        = $report->getCost();
                $ref[$k7]    += (($repartition->getRatio() / 100) * ($cost['EUR'] ?? 0));
                $ref[$k8]    += (($repartition->getRatio() / 100) * ($cost['USD'] ?? 0));
            }
        }

        foreach ($structures as $structureId => $types) {
            foreach ($types as $type => $data) {
                $ref = &$structures[$structureId][$type];
                if ($ref[$k9]) {
                    $ref[$k6] = $ref[$k4] * 100 / $ref[$k9];
                }
                $ref[$k11] = count(array_unique($ref[$k11]));
            }
        }

        $final = [];
        foreach ($structures as $structureId => $types) {
            foreach ($types as $type => $data) {
                $final[sprintf('%d-%s', $structureId, $type)] = $data;
            }
        }

        ksort($final, SORT_NUMERIC);

        $csv = Writer::createFromString();
        $csv->setOutputBOM(Reader::BOM_UTF8);
        $csv->setDelimiter(';');
        $csv->insertOne(array_keys(reset($final)));
        $csv->insertAll($final);

        echo $csv->getContent();

        return 0;
    }
}