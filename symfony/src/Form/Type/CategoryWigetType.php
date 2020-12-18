<?php

namespace App\Form\Type;

use App\Entity\Category;
use App\Manager\CategoryManager;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CategoryWigetType extends TextType
{
    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @param CategoryManager $categoryManager
     */
    public function __construct(CategoryManager $categoryManager)
    {
        $this->categoryManager = $categoryManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?Category $category) {
                    return $category ? $category->getId() : null;
                },
                function (?int $categoryId) {
                    return $categoryId ? $this->categoryManager->find($categoryId) : null;
                }
            )
        );
    }

    public function getBlockPrefix()
    {
        return 'category_widget';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $category = $view->vars['value'];
            if (!$category instanceof Category) {
                $category = $this->categoryManager->find($view->vars['value']);
                if ($category) {
                    $view->vars['data'] = [$category->toSearchResults()];
                }
            }
        }
    }
}