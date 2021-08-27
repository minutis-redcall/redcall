<?php

namespace App\Controller\Api;

use App\Entity\Structure;
use App\Facade\Structure\StructureFiltersFacade;
use App\Facade\Structure\StructureReadFacade;
use App\Manager\StructureManager;
use App\Transformer\ResourceTransformer;
use App\Transformer\StructureTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Base\BaseController;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Structures are independent Red Cross sections that manage volunteers and organize operations.
 *
 * @Route("/api/structure", name="api_structure_")
 */
class StructureController extends BaseController
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var StructureTransformer
     */
    private $structureTransformer;

    /**
     * @var ResourceTransformer
     */
    private $resourceTransformer;

    public function __construct(StructureManager $structureManager,
        StructureTransformer $structureTransformer,
        ResourceTransformer $resourceTransformer)
    {
        $this->structureManager     = $structureManager;
        $this->structureTransformer = $structureTransformer;
        $this->resourceTransformer  = $resourceTransformer;
    }

    /**
     * List all structures.
     *
     * @Endpoint(
     *   priority = 300,
     *   request  = @Facade(class     = StructureFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = StructureReadFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(StructureFiltersFacade $filters) : FacadeInterface
    {
        $qb = $this->structureManager->searchQueryBuilder($filters->getCriteria(), $filters->isOnlyEnabled());

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Structure $structure) {
            return $this->structureTransformer->expose($structure);
        });
    }
}