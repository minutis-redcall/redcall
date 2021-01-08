<?php

namespace App\Form\Type;

use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        Security $security)
    {
        $this->userManager      = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->badgeManager     = $badgeManager;
        $this->security         = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $structures      = $this->userManager->findForCurrentUser()->getStructures();
        $hasOneStructure = $structures->count();

        $builder
            ->add('volunteers', HiddenType::class, [
                'label'    => 'audience.search_for_volunteers',
                'required' => false,
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => 'audience.copy_paste_details',
                'required' => false,
                'attr'     => [
                    'rows' => 4,
                ],
            ])
            ->add('structures_global', HiddenType::class, [
                'required' => false,
                'data'     => $hasOneStructure ? $structures->first()->getId() : null,
            ])
            ->add('structures_local', HiddenType::class, [
                'required' => false,
            ])
            ->add('badges_all', CheckboxType::class, [
                'label'    => 'audience.select_all_badges',
                'required' => false,
            ])
            ->add('badges_ticked', TextType::class, [
                'required' => false,
            ])
            ->add('badges_searched', TextType::class, [
                'label'    => 'audience.search_other_badge',
                'required' => false,
            ])
            ->add('test_on_me', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->buildVolunteerView($view, $form);
        $this->buildStructureView($view);
        $this->buildBadgeView($view, $form);
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

    private function buildVolunteerView(FormView $view, FormInterface $form)
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
     * Building the structures tree using as few requests as possible.
     *
     * ** WARNING **
     *
     * Global counts are only estimates: I currently do the sum of children structure counts
     * in order to have the global volunteer count for a parent structure, but this does not
     * take into account volunteers that are in several children structures.
     *
     * Example: user 42 is in structures "Paris" because he has departmental role, but is
     * tied to "Paris 1er", so he can be triggered in both structures. In the "Paris"
     * count, we'll count this volunteer twice.
     *
     * @param FormView $view
     */
    private function buildStructureView(FormView $view)
    {
        // Structures hierarchy
        $hierarchy = [];
        foreach ($this->structureManager->getStructureHierarchyForCurrentUser() as $row) {
            if (!array_key_exists($row['id'], $hierarchy)) {
                $hierarchy[$row['id']] = [];
            }
            if ($row['child_id']) {
                $hierarchy[$row['id']][] = $row['child_id'];
            }
        }

        // Finding roots
        $roots    = [];
        $children = call_user_func_array('array_merge', $hierarchy);
        foreach (array_keys($hierarchy) as $id) {
            if (!in_array($id, $children)) {
                $roots[] = $id;
            }
        }

        // Basic information
        $ids         = array_merge(
            array_keys($hierarchy),
            $children
        );
        $information = [];
        foreach ($this->structureManager->getVolunteerCounts($ids) as $entry) {
            $information[$entry['id']] = [
                'name'         => $entry['name'],
                'local_count'  => intval($entry['count']),
                'global_count' => 0,
            ];
        }

        // Estimate of the number of people globally
        $counts = [];
        foreach ($hierarchy as $root => $children) {
            if (!array_key_exists($root, $counts)) {
                $this->createGlobalCount($information, $hierarchy, $counts, $root);
            }
            $information[$root]['global_count'] = $counts[$root];
        }
        $view->vars['structures_information'] = $information;

        // Alphabetic hierarchy order
        usort($roots, function ($a, $b) use ($information) {
            return $information[$a]['name'] <=> $information[$b]['name'];
        });
        $view->vars['structures_roots'] = $roots;

        foreach ($hierarchy as $row => $children) {
            usort($hierarchy[$row], function ($a, $b) use ($information) {
                return $information[$a]['name'] <=> $information[$b]['name'];
            });
        }
        $view->vars['structures_hierarchy'] = $hierarchy;
    }

    /**
     * This method counts volunteers per structure, including volunteers in children structures.
     *
     * It is only an estimate through, because this way does not take into account volunteers that are
     * in several structures of the hierarchy.
     *
     * @param array $information
     * @param array $hierarchy
     * @param array $counts
     * @param int   $root
     */
    private function createGlobalCount(array &$information, array &$hierarchy, array &$counts, int $root)
    {
        $count = $information[$root]['local_count'];

        foreach ($hierarchy[$root] as $child) {
            if (!array_key_exists($child, $counts)) {
                $this->createGlobalCount($information, $hierarchy, $counts, $child);
            }

            $count += $counts[$child];
        }

        $counts[$root] = $count;
    }

    private function buildBadgeView(FormView $view, FormInterface $form)
    {
        // Badges selection
        $view->vars['badges_public']   = $this->badgeManager->getPublicBadges();
        $view->vars['badges_searched'] = [];

        if ($ids = explode(',', $form->get('badges_searched')->getData())) {

            // TODO this method does not exist
            $badges = $this->badgeManager->getNonVisibleUsableBadges($ids);
            //

            //            $view->vars['volunteers_data'] = array_map(function (Volunteer $volunteer) {
            //                return $volunteer->toSearchResults();
            //            }, $volunteers);
        }

    }
}

;