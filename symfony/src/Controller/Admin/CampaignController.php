<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Manager\CampaignManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/campaign", name="admin_campaign_")
 */
class CampaignController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    public function __construct(PaginationManager $paginationManager, CampaignManager $campaignManager)
    {
        $this->paginationManager = $paginationManager;
        $this->campaignManager   = $campaignManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/campaign/list.html.twig")
     *
     * @return array
     */
    public function index() : array
    {
        $all = $this->campaignManager->getAllCampaignsQueryBuilder(
            $this->getPlatform()
        );

        return [
            'type'  => 'all',
            'table' => [
                'orderBy' => $this->orderBy($all, Campaign::class, 'c.createdAt', 'DESC', 'all'),
                'pager'   => $this->paginationManager->getPager($all, 'all'),
            ],
        ];
    }
}
