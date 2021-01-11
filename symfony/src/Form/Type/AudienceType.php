<?php

namespace App\Form\Type;

use App\Manager\AudienceManager;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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
    // Lists are hidden fields transformed to array of ids
    const LISTS = [
        'volunteers',
        'nivols',
        'structures_global',
        'structures_local',
        'badges_ticked',
        'badges_searched',
    ];

    /**
     * @var AudienceManager
     */
    private $audienceManager;

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

    public function __construct(AudienceManager $audienceManager,
        UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        Security $security)
    {
        $this->audienceManager  = $audienceManager;
        $this->userManager      = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->badgeManager     = $badgeManager;
        $this->security         = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $structures      = $this->userManager->findForCurrentUser()->getStructures();
        $hasOneStructure = 1 === $structures->count();

        $builder
            ->add('volunteers', HiddenType::class, [
                'label'    => 'audience.search_for_volunteers',
                'required' => false,
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => 'audience.copy_paste_details',
                'required' => false,
                'data'     => ['foobar', 'baz', 'toto'],
                'attr'     => [
                    'rows' => 4,
                ],
            ])
            ->add('structures_global', HiddenType::class, [
                'required' => false,
                'data'     => [1044], /* $hasOneStructure ? [$structures->first()->getId()] : null, */
            ])
            ->add('structures_local', HiddenType::class, [
                'required' => false,
            ])
            ->add('badges_all', CheckboxType::class, [
                'label'    => 'audience.select_all_badges',
                'required' => false,
            ])
            ->add('badges_ticked', HiddenType::class, [
                'required' => false,
                'data'     => [489],
            ])
            ->add('badges_searched', TextType::class, [
                'label'    => 'audience.search_other_badge',
                'required' => false,
                'data'     => [567, 544],
            ])
            ->add('test_on_me', CheckboxType::class, [
                'required' => false,
            ]);

        $lists = self::LISTS;
        foreach ($lists as $list) {
            $builder->get($list)->addModelTransformer(new CallbackTransformer(
                function (?array $fromModel) {
                    return $fromModel ? implode(',', $fromModel) : null;
                },
                function (?string $fromView) {
                    return $fromView ? array_filter(explode(',', $fromView)) : null;
                }
            ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Volunteer view
        $view->vars['volunteers_data'] = [];
        if ($ids = $form->get('volunteers')->getData()) {
            $view->vars['volunteers_data'] = $this->audienceManager->getVolunteerList($ids);
        }

        $this->buildStructureView($view);

        // Badge view
        $publicBadges                  = $this->badgeManager->getPublicBadges();
        $view->vars['badges_public']   = $publicBadges;
        $view->vars['badges_searched'] = [];
        if ($ids = $form->get('badges_searched')->getData()) {
            $view->vars['badges_searched'] = $this->audienceManager->getBadgeList($ids);
        }

        // Preparing initial selection classification
        $data = [];
        foreach ($form as $name => $element) {
            $data[$name] = $element->getData();
        }
        $view->vars['classification'] = $this->audienceManager->classifyAudience($data);
        $view->vars['badge_counts']   = $this->audienceManager->extractBadgeCounts($data, $publicBadges);
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
        foreach ($this->structureManager->getVolunteerLocalCounts($ids) as $entry) {
            $information[$entry['id']] = [
                'name'         => $entry['name'],
                'local_count'  => intval($entry['count']),
                'global_count' => 0,
            ];
        }

        // Calculating global counts
        foreach ($hierarchy as $id => $children) {
            if (!$children) {
                $information[$id]['global_count'] = $information[$id]['local_count'];
            } else {
                $descendants                      = $this->findDescendants($hierarchy, array_merge([$id], $children));
                $information[$id]['global_count'] = $this->volunteerManager->getVolunteerGlobalCounts($descendants);
            }
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

    private function findDescendants(array &$hierarchy, array $children) : array
    {
        $ids = [];

        foreach ($children as $child) {
            $ids[] = $child;
            if ($hierarchy[$child]) {
                $ids = array_merge($ids, $this->findDescendants($hierarchy, $hierarchy[$child]));
            }
        }

        return $ids;
    }
}

;