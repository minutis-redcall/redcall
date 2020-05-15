<?php

namespace App\Form\Type;

use App\Manager\StructureManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class StructureWidgetType extends TextType
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    public function getBlockPrefix()
    {
        return 'structure_widget';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $structure = $this->structureManager->findOneByName($view->vars['value']);
            if ($structure) {
                $view->vars['data'] = [$structure->toSearchResults()];
            }
        }
    }
}