<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\PrefilledAnswers;
use App\Form\Type\PrefilledAnswersType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="admin/reponses-pre-remplies/", name="admin_prefilled_answers_")
 */
class PrefilledAnswersController extends BaseController
{
    /**
     * @Route(name="list")
     */
    public function listAction()
    {
        $pfas = $this
            ->getManager(PrefilledAnswers::class)
            ->createQueryBuilder('p');

        return $this->render('admin/prefilled_answers/list.html.twig', [
            'pager' => $this->getPager($pfas),
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
            $pfa = $this->getPrefilledAnswser($pfaId);
        }

        $form = $this
            ->createForm(PrefilledAnswersType::class, $pfa)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getManager();
            $em->persist($pfa);
            $em->flush();

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
     *     path="supprimer/{pfaId}/{csrf}",
     *     requirements={"pfaId" = "\d+"}
     * )
     */
    public function deleteAction(int $pfaId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('prefilled_answers', $csrf);

        $pfa = $this->getPrefilledAnswser($pfaId);

        $this->getManager()->remove($pfa);
        $this->getManager()->flush();

        return $this->redirectToRoute('admin_prefilled_answers_list');
    }

    private function getPrefilledAnswser(int $pfaId)
    {
        $pfa = $this->getManager(PrefilledAnswers::class)->find($pfaId);

        if (is_null($pfa)) {
            throw $this->createNotFoundException();
        }

        return $pfa;
    }
}
