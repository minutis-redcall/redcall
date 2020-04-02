<?php

namespace App\Controller\Management\Structure;



use App\Base\BaseController;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Form\Type\PrefilledAnswersType;
use App\Manager\PrefilledAnswersManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/structures/{structure}/prefilled-answers", name="management_structures_prefilled_answers_")
 * @ParamConverter("structure", options={"id" = "structure"})
 */
class PrefilledAnswersController extends BaseController
{

    /**
     * @var PrefilledAnswersManager
     */
    private $prefilledAnswersManager;

    public function __construct(PrefilledAnswersManager $prefilledAnswersManager)
    {
        $this->prefilledAnswersManager = $prefilledAnswersManager;
    }

    /**
     * @Route("/", name="list")
     * @Template("management/structures/prefilled_answers/list.html.twig")
     *
     * @param Structure $structure
     *
     * @return array
     */
    public function listPrefilledAnswers(Structure $structure)
    {
        $prefilledAnswers = $this->prefilledAnswersManager->getPrefilledAnswersByStructure($structure);

        return ['pager' => $this->getPager($prefilledAnswers), 'structure' => $structure];
    }


    /**
     * @Route("/{prefilledAnswers}/editor", requirements={"prefilledAnswers" = "\d+"}, name="edit")
     * @Route("/new", name="new")
     * @Template("management/structures/prefilled_answers/editor.html.twig")
     *
     * @param Structure $structure
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editorPrefilledAnswers(Request $request, Structure $structure)
    {
        if($request->get('prefilledAnswers') === null) {
            $prefilledAnswers = new PrefilledAnswers();
            $prefilledAnswers->setStructure($structure);
        } else {
            $prefilledAnswers = $this->prefilledAnswersManager->findById($request->get('prefilledAnswers'));
        }

        $form = $this
            ->createForm(PrefilledAnswersType::class, $prefilledAnswers)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getManager();
            $em->persist($prefilledAnswers);
            $em->flush();

            return $this->redirectToRoute('management_structures_prefilled_answers_list', ['structure' => $structure->getId()]);
        }


        return ['form' => $form->createView(), 'structure' => $structure];
    }

}