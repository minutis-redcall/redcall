<?php

namespace App\Form\Type;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NivolsWidgetType extends AbstractType
{

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(UrlGeneratorInterface $urlGenerator, VolunteerManager $volunteerManager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->volunteerManager = $volunteerManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nivols', TextType::class, [
            'attr' => [
                'class' => 'flexdatalist',
                'data-url' => $this->urlGenerator->generate('widget_nivol_search'),
                'data-min-length'=> 1,
                'data-visible-properties'=> '["nivol", "lastName", "firstName"]',
                'data-focus-first-result'=> true,
                'data-selection-required'=> true,
                'data-text-property'=> '{nivol} - {lastName} {firstName}',
                'data-search-in' => 'nivol',
                'multiple' => 'multiple',
                'data-value-property' => 'nivol'
            ]
        ])
            ->addModelTransformer(new CallbackTransformer(
                function (?array $volunteersAsArray) {
                    //TO ARRAY
                    return [
                        'nivols' => implode(',', array_map(function (Volunteer $volunteer) {
                            return $volunteer->getId();
                        }, $volunteersAsArray ?? [])),
                    ];
                },
                function (?array $nivolsToEntity) {
                    //TO ENTITY
                    return array_filter(array_map(function($nivol) {
                        return $this->volunteerManager->findOneByNivol($nivol);
                    }, explode(',', strval($nivolsToEntity['nivols']))));
                }
            ));
    }
}
