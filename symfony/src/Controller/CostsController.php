<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\User;
use App\Manager\ReportManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/costs", name="costs_")
 * @IsGranted("ROLE_TRUSTED")
 */
class CostsController extends BaseController
{
    private ReportManager $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * @Route("/", name="home")
     * @Template
     */
    public function home(): array
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get user's structure IDs
        $structureIds = array_map(function ($structure) {
            return $structure->getId();
        }, $user->getStructures()->toArray());

        if (empty($structureIds)) {
            return [
                'structureReports' => [],
                'monthlyTotals' => [],
                'lastMonth' => null,
                'lastMonthLabel' => null,
            ];
        }

        // Calculate last finished month
        // Today is Jan 15, 2026 -> last finished month is December 2025
        $now = new \DateTime();
        $lastMonthEnd = (clone $now)->modify('last day of previous month')->setTime(23, 59, 59);
        $lastMonthStart = (clone $now)->modify('first day of previous month')->setTime(0, 0, 0);

        // Get detailed reports for last finished month, per user structure
        $structureReports = $this->reportManager->createUserStructuresCostsReport(
            $structureIds,
            $lastMonthStart,
            $lastMonthEnd
        );

        // Get 12-month totals
        $monthlyTotals = $this->reportManager->createUserStructuresMonthlyTotals($structureIds, 12);

        return [
            'structureReports' => $structureReports,
            'monthlyTotals' => $monthlyTotals,
            'lastMonthStart' => $lastMonthStart,
            'lastMonthEnd' => $lastMonthEnd,
            'lastMonthLabel' => $lastMonthStart->format('F Y'),
        ];
    }
}
