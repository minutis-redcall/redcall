<?php

namespace App\Form\Type;

use App\Entity\Volunteer;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AudienceType extends AbstractType
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager, VolunteerManager $volunteerManager, Security $security)
    {
        $this->userManager      = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->security         = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('volunteers', TextType::class, [
                'label'    => 'audience.filters.search_for_volunteers',
                'required' => false,
                'data'     => '78,1165,1272,1382,2191',
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => 'audience.filters.copy_paste_details',
                'required' => false,
                'attr'     => [
                    'rows' => 4,
                ],
            ])
            ->add('structures', TextType::class, [
                'required' => false,
            ])
            ->add('tags', TextType::class, [
                'required' => false,
            ])
            ->add('test_on_me', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Loading the required flexdatalist data in order to initialize selected volunteers
        $view->vars['volunteers_data'] = [];
        if ($ids = explode(',', $form->get('volunteers')->getData())) {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $volunteers = $this->volunteerManager->getVolunteerList($ids);
            } else {
                $volunteers = $this->volunteerManager->getVolunteerListForCurrentUser($ids);
            }

            $view->vars['volunteers_data'] = array_map(function (Volunteer $volunteer) {
                return $volunteer->toSearchResults();
            }, $volunteers);
        }

    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'          => false,
            'error_bubbling' => false,
            'constraints'    => [
                // todo
            ],
        ]);
    }

    public function getBlockPrefix() : string
    {
        return 'audience';
    }
}