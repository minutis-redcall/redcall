<?php

namespace App\Form\Type;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        $builder
            ->add('nivols', TextareaType::class, [
                'label' => false,
            ])
            ->addModelTransformer(new CallbackTransformer(
                function (?array $volunteersAsArray) {
                    return [
                        'nivols' => implode(',', array_map(function (Volunteer $volunteer) {
                            return $volunteer->getNivol();
                        }, $volunteersAsArray ?? [])),
                    ];
                },
                function (?array $nivolsToEntity) {
                    $nivols = array_filter(preg_split('/[^0-9a-z*]/ui', $nivolsToEntity['nivols']));

                    return $this->volunteerManager->filterByNivolAndAccess($nivols);
                }
            ));
    }
}
