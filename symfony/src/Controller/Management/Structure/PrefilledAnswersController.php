<?php

namespace App\Controller\Management\Structure;

use App\Base\BaseController;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Form\Type\PrefilledAnswersType;
use App\Manager\PrefilledAnswersManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/structures/{structure}/prefilled-answers", name="management_structures_prefilled_answers_")
 * @ParamConverter("structure", options={"id" = "structure"})
 * @Security("is_granted('STRUCTURE', structure)")
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
     * @Route("/", name="list")
     * @Template("management/structures/prefilled_answers/list.html.twig")
     */
    public function listPrefilledAnswers(Structure $structure)
    {
        $prefilledAnswers = $this->prefilledAnswersManager->getPrefilledAnswersByStructure($structure);

        return ['pager' => $this->paginationManager->getPager($prefilledAnswers), 'structure' => $structure];
    }

    /**
     * @Route("/{prefilledAnswers}/editor", requirements={"prefilledAnswers" = "\d+"}, name="edit")
     * @Route("/new", name="new")
     * @Template("management/structures/prefilled_answers/editor.html.twig")
     */
    public function editorPrefilledAnswers(Request $request, Structure $structure)
    {
        if ($request->get('prefilledAnswers') === null) {
            $prefilledAnswers = new PrefilledAnswers();
            $prefilledAnswers->setPlatform($this->getPlatform());
            $prefilledAnswers->setStructure($structure);
        } else {
            $prefilledAnswers = $this->prefilledAnswersManager->findById($request->get('prefilledAnswers'));
            if (!$prefilledAnswers) {
                throw $this->createNotFoundException();
            }
        }

        $form = $this
            ->createForm(PrefilledAnswersType::class, $prefilledAnswers)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prefilledAnswersManager->save($prefilledAnswers);

            return $this->redirectToRoute('management_structures_prefilled_answers_list', ['structure' => $structure->getId()]);
        }

        return ['form' => $form->createView(), 'structure' => $structure];
    }

    /**
     * @Route("/{prefilledAnswers}/delete", requirements={"prefilledAnswers" = "\d+"}, name="delete")
     */
    public function deleteAction(Request $request, PrefilledAnswers $prefilledAnswers, Structure $structure)
    {
        $this->validateCsrfOrThrowNotFoundException('prefilled_answers', $request->get('csrf'));

        $this->prefilledAnswersManager->remove($prefilledAnswers);

        return $this->redirectToRoute('management_structures_prefilled_answers_list', ['structure' => $structure->getId()]);
    }
}
