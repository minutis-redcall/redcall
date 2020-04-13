<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NivolsWidgetType extends AbstractType
{

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nivols', TextType::class, [
            'attr' => [
                'class' => 'flexdatalist',
                'data-url' => $this->urlGenerator->generate('widget_nivol_search', ['searchAll' => true]),
                'data-min-length'=> 1,
                'data-visible-properties'=> '["nivol", "lastName", "firstName"]',
                'data-focus-first-result'=> true,
                'data-selection-required'=> true,
                'data-text-property'=> '{nivol} - {lastName} {firstName}',
                'data-search-in' => 'nivol',
                'multiple' => 'multiple'
            ]
        ])
            ;
    }
}
