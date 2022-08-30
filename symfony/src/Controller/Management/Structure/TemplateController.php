<?php

namespace App\Controller\Management\Structure;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Entity\Template;
use App\Form\Type\TemplateType;
use App\Manager\TemplateManager;
use App\Model\Csrf;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template as TwigTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/structures/{structure}/template", name="management_structures_template_")
 * @ParamConverter("structure", options={"id" = "structure"})
 * @ParamConverter("template", options={"id" = "template"})
 * @Security("is_granted('STRUCTURE', structure)")
 */
class TemplateController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var TemplateManager
     */
    private $templateManager;

    public function __construct(PaginationManager $paginationManager, TemplateManager $templateManager)
    {
        $this->paginationManager = $paginationManager;
        $this->templateManager   = $templateManager;
    }

    /**
     * @Route(name="list")
     * @TwigTemplate("management/structures/template/list.html.twig")
     */
    public function list(Structure $structure)
    {
        $templates = $this->templateManager->getTemplatesForStructure($structure);

        return [
            'pager'     => $this->paginationManager->getPager($templates),
            'structure' => $structure,
        ];
    }

    /**
     * @Route("/new", name="new")
     * @Route("/{template}/edit", requirements={"template" = "\d+"}, name="edit")
     * @TwigTemplate("management/structures/template/editor.html.twig")
     */
    public function editor(Request $request, Structure $structure, ?Template $template = null)
    {
        if ($template && !$template->getStructure()->isEqualTo($structure)) {
            throw $this->createAccessDeniedException();
        }

        if (!$template) {
            $template = new Template();
            $template->setStructure($structure);
        }

        $form = $this
            ->createForm(TemplateType::class, $template)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->templateManager->add($template);

            return $this->redirectToRoute('management_structures_template_list', [
                'structure' => $structure->getId(),
            ]);
        }

        return [
            'template'  => $template,
            'structure' => $structure,
            'form'      => $form->createView(),
        ];
    }

    /**
     * @Route("/{template}/{csrf}/delete", name="delete")
     */
    public function delete(Structure $structure, Template $template, Csrf $csrf)
    {
        if (!$template->getStructure()->isEqualTo($structure)) {
            throw $this->createAccessDeniedException();
        }

        $this->templateManager->remove($template);

        return $this->redirectToRoute('management_structures_template_list', [
            'structure' => $structure->getId(),
        ]);
    }

    /**
     * @Route("/{template}/{csrf}/move/{newPriority}", name="move")
     */
    public function move(Structure $structure, Template $template, int $newPriority, Csrf $csrf)
    {
        if (!$template->getStructure()->isEqualTo($structure)) {
            throw $this->createAccessDeniedException();
        }

        $template->setPriority($newPriority);

        $this->templateManager->add($template);

        return $this->redirectToRoute('management_structures_template_list', [
            'structure' => $structure->getId(),
        ]);
    }
}