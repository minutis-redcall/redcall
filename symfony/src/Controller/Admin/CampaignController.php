<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Manager\CampaignManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/campaign", name="admin_campaign_")
 */
class CampaignController extends BaseController
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    public function __construct(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/campaign/index.html.twig")
     *
     * @return array
     */
    public function index() : array
    {
        return [];
    }

    /**
     * @Template("admin/campaign/table.html.twig")
     *
     * @return array
     */
    public function renderCampaignsTable() : array
    {
        $all = $this->campaignManager->getAllCampaignsQueryBuilder();

        return [
            'all' => [
                'orderBy' => $this->orderBy($all, Campaign::class, 'c.createdAt', 'DESC', 'all'),
                'pager'   => $this->getPager($all),
            ],
        ];
    }
}
