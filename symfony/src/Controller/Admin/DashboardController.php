<?php

namespace App\Controller\Admin;


use App\Base\BaseController;
use App\Manager\StatisticsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/dashboard", name="admin_dashboard_")
 */
class DashboardController extends BaseController
{
    /**
     * @Route("/statistics", name="statistics")
     * @Template("admin/dashboard/statistics.html.twig")
     *
     * @param StatisticsManager $statisticsManager
     * @param Request           $request
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function statistics(StatisticsManager $statisticsManager, Request $request): array
    {
        $from = $request->query->get('from') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('from')) : new \DateTime('-7days');
        $to = $request->query->get('to') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('to')) : new \DateTime();

        $from->setTime(0, 0,0);
        $to->setTime(23, 59, 59);

        return ['stats' => $statisticsManager->getDashboardStatistics($from, $to),
                'from'  => $from,
                'to'    => $to
        ];
    }
}