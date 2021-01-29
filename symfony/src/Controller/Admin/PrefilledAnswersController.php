<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\PrefilledAnswers;
use App\Form\Type\PrefilledAnswersType;
use App\Manager\PrefilledAnswersManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="admin/reponses-pre-remplies/", name="admin_prefilled_answers_")
 */
class PrefilledAnswersController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var PrefilledAnswersManager
     */
    private $prefilledAnswersManager;

    public function __construct(PaginationManager $paginationManager, PrefilledAnswersManager $prefilledAnswersManager)
    {
        $this->paginationManager       = $paginationManager;
        $this->prefilledAnswersManager = $prefilledAnswersManager;
    }

    /**
     * @Route(name="list")
     */
    public function listAction()
    {
        $prefilledAnswers = $this->prefilledAnswersManager->getGlobalPrefilledAnswers();

        return $this->render('admin/prefilled_answers/list.html.twig', [
            'pager' => $this->paginationManager->getPager($prefilledAnswers),
        ]);
    }

    /**
     * @Route(
     *     name="editor",
     *     path="editer/{pfaId}",
     *     defaults={"pfaId": null},
     *     requirements={"pfaId" = "\d+"}
     * )
     */
    public function editorAction(Request $request, ?int $pfaId = null)
    {
        $pfa = new PrefilledAnswers();

        if ($pfaId) {
            $pfa = $this->prefilledAnswersManager->findById($pfaId);
            if (!$pfa) {
                throw $this->createNotFoundException();
            }
        }

        $form = $this
            ->createForm(PrefilledAnswersType::class, $pfa)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prefilledAnswersManager->save($pfa);

            return $this->redirectToRoute('admin_prefilled_answers_list');
        }

        return $this->render('admin/prefilled_answers/editor.html.twig', [
            'prefilled_answer' => $pfa,
            'form'             => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     name="delete",
     *     path="supprimer/{csrf}/{pfaId}",
     *     requirements={"pfaId" = "\d+"}
     * )
     * @ParamConverter("prefilledAnswers", options={"id"= "pfaId"})
     */
    public function deleteAction(PrefilledAnswers $prefilledAnswers, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('prefilled_answers', $csrf);

        $this->prefilledAnswersManager->remove($prefilledAnswers);

        return $this->redirectToRoute('admin_prefilled_answers_list');
    }
}
