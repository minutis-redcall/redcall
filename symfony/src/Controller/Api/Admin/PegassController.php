<?php

namespace App\Controller\Api\Admin;

use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/admin/pegass", name="api_admin_pegass_")
 * @IsGranted("ROLE_ADMIN")
 */
class PegassController
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @Route(name="records", methods={"GET"})
     */
    public function records(Request $request)
    {
        $qb = $this->pegassManager->getAllEnabledEntitiesQueryBuilder();

        // Pagination ?
        // QueryBuilderNormalizer ?
    }

}