<?php

namespace Bundles\ChartBundle\Controller;

use Bundles\ChartBundle\Entity\StatQuery;
use Bundles\ChartBundle\Form\Type\QueryType;
use Bundles\ChartBundle\Manager\QueryManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN")
 * @IsGranted("ROLE_DEVELOPER")
 * @Route(name="chart_query_", path="/chart/query")
 */
class QueryController extends AbstractController
{
    /**
     * @var QueryManager
     */
    private $queryManager;

    public function __construct(QueryManager $queryManager)
    {
        $this->queryManager = $queryManager;
    }

    /**
     * @Template
     * @Route(name="home")
     */
    public function index()
    {
        return [
            'queries' => $this->queryManager->findAll(),
        ];
    }

    /**
     * @Template
     * @Route(name="edit", path="/edit/{id}", defaults={"id": null})
     */
    public function edit(Request $request, ?StatQuery $query)
    {
        $query = $query ?: new StatQuery();

        $form = $this->createForm(QueryType::class, $query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // ...

        }

        return [
            'query' => $query,
            'form'  => $form->createView(),
        ];
    }


}