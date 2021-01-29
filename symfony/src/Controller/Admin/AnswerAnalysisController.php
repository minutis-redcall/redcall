<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\AnswerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/answer-analysis", name="admin_answer_analysis_")
 */
class AnswerAnalysisController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var AnswerManager
     */
    private $answerManager;

    public function __construct(PaginationManager $paginationManager)
    {
        $this->paginationManager = $paginationManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/answer_analysis/index.html.twig")
     */
    public function index(Request $request) : array
    {
        $form = $this->createSearchForm($request);

        $queryBuilder = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $queryBuilder = $this->answerManager->getSearchQueryBuilder(
                $form->get('criteria')->getData() ?? ''
            );
        }

        return [
            'form'  => $form->createView(),
            'pager' => $queryBuilder ? $this->paginationManager->getPager($queryBuilder) : null,
        ];
    }

    private function createSearchForm(Request $request) : FormInterface
    {
        return $this->createFormBuilder()
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => 'admin.answer_analysis.search.label',
                        'required' => false,
                        'data'     => 'parti quit vir plus stop crf croix retir',
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'admin.answer_analysis.search.button',
                        'attr'  => [
                            'class' => 'btn btn-secondary',
                        ],
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}