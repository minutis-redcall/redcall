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

    /**
     * @param StructureManager $structureManager
     */
    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'structure';
    }

    /**
     * {@inheritdoc}
     */
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