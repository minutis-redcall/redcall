<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Component\HttpFoundation\ArrayToCsvResponse;
use App\Manager\ReportManager;
use App\Manager\StatisticsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @Route("/admin/stats", name="admin_stats_")
 */
class StatsController extends BaseController
{
    /**
     * @var ReportManager
     */
    private $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * @Route("/", name="home")
     * @Template
     */
    public function index()
    {
        return [];
    }

    /**
     * @Route("/general", name="general")
     * @Template()
     */
    public function general(StatisticsManager $statisticsManager, Request $request) : array
    {
        //$from = new \DateTime('first day of January this year midnight');
        $from = new \DateTime('first day of this month midnight');
        $to   = new \DateTime();

        $form = $this
            ->createFormBuilder([
                'from' => $from,
                'to'   => $to,
            ])
            ->add('from', DateType::class, [
                'label'       => 'admin.statistics.general.form.from',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('to', DateType::class, [
                'label'       => 'admin.statistics.general.form.to',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $from = $form->get('from')->getData();
            $to   = $form->get('to')->getData();
        }

        $from->setTime(0, 0);
        $to->setTime(23, 59, 59);

        return [
            'stats' => $statisticsManager->getDashboardStatistics($from, $to),
            'from'  => $from,
            'to'    => $to,
            'form'  => $form->createView(),
        ];
    }

    /**
     * @Route("/structure", name="structure")
     * @Template
     */
    public function structure(Request $request)
    {
        $form = $this->createStructureForm($request);

        $report = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $from = $form->get('from')->getData();
            $to   = $form->get('to')->getData();
            $min  = (int) $form->get('min')->getData();

            $report = $this->reportManager->createStructureReport($from, $to, $min);

            // If "Download CSV" was clicked, return a CSV response instead of HTML.
            if ($form->get('download_csv')->isClicked()) {
                $rows = $this->buildStructureReportCsvRows($report);

                $filename = sprintf(
                    'structure-stats_%s_%s_min-%d.csv',
                    $from->format('Y-m-d'),
                    $to->format('Y-m-d'),
                    $min
                );

                return new ArrayToCsvResponse($rows, $filename);
            }
        }

        return [
            'form'   => $form->createView(),
            'report' => $report,
        ];
    }

    private function createStructureForm(Request $request) : FormInterface
    {
        return $this
            ->createFormBuilder([
                'from' => new \DateTime(sprintf('%d-01-01', (new \DateTime())->format('Y') - 1)),
                'to'   => new \DateTime(sprintf('%d-12-31', (new \DateTime())->format('Y') - 1)),
                'min'  => 5,
            ])
            ->add('from', DateType::class, [
                'label'       => 'admin.statistics.structure.form.from',
                'widget'      => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('to', DateType::class, [
                'label'       => 'admin.statistics.structure.form.to',
                'widget'      => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('min', NumberType::class, [
                'label'       => 'admin.statistics.structure.form.min',
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 1]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
                'attr'  => [
                    'class' => 'btn btn-primary trigger-launch',
                ],
            ])
            ->add('download_csv', SubmitType::class, [
                'label' => 'CSV',
                'attr'  => [
                    'class' => 'btn btn-secondary',
                ],
            ])
            ->getForm()
            ->handleRequest($request);
    }

    /**
     * Flattens the structure report array into CSV rows.
     * One row per (structure, trigger type) like your HTML table.
     */
    private function buildStructureReportCsvRows(array $report) : array
    {
        $rows = [];

        foreach ($report as $structureId => $types) {
            foreach ($types as $type => $data) {
                if (!isset($data['costs']) || count($data['costs']) === 0) {
                    $cost     = 0;
                    $currency = 'EUR';
                } else {
                    $cost     = reset($data['costs']);
                    $currency = key($data['costs']);
                }

                $rows[] = [
                    'ID de la structure'  => (string) $structureId,
                    'Nom de la structure' => (string) ($data['name'] ?? ''),
                    'Déclenchements'      => (string) ($data['campaigns'] ?? 0),
                    'Type'                => (string) ($data['type'] ?? $type),
                    'Communications'      => (string) ($data['communications'] ?? 0),
                    'Messages'            => (string) ($data['messages'] ?? 0),
                    'Questions'           => (string) ($data['questions'] ?? 0),
                    'Réponses'            => (string) ($data['answers'] ?? 0),
                    'Erreurs'             => (string) ($data['errors'] ?? 0),
                    'Coûts'               => sprintf('%.2f', abs($cost)),
                    'Devise'              => $currency,
                ];
            }
        }

        return $rows;
    }
}