<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Campaign;
use App\Manager\CampaignManager;
use App\Provider\Minutis\MinutisProvider;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/admin/campaign", name: "admin_campaign_")]
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
     *
     * @return array
     */
#[Route(name: "index")]
#[Template("admin/campaign/list.html.twig")]
    public function index(MinutisProvider $minutis) : array
    {
        $all = $this->campaignManager->getAllCampaignsQueryBuilder();

        return [
            'minutis' => $minutis,
            'type'    => 'all',
            'table'   => [
                'orderBy' => $this->orderBy($all, Campaign::class, 'c.createdAt', 'DESC', 'all'),
                'pager'   => $this->paginationManager->getPager($all, 'all'),
            ],
        ];
    }
}
