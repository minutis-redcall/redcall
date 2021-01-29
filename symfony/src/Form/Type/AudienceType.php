<?php

namespace App\Form\Type;

use App\Manager\AudienceManager;
use App\Manager\BadgeManager;
use App\Manager\ExpirableManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class AudienceType extends AbstractType
{
    // Lists are hidden fields transformed to array of ids
    const LISTS = [
        'volunteers',
        'excluded_volunteers',
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
     * @var ExpirableManager
     */
    private $expirableManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(AudienceManager $audienceManager,
        UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        ExpirableManager $expirableManager,
        Security $security)
    {
        $this->audienceManager  = $audienceManager;
        $this->userManager      = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->badgeManager     = $badgeManager;
        $this->expirableManager = $expirableManager;
        $this->security         = $security;
    }

    static public function createEmptyData(array $defaults) : array
    {
        return array_merge([
            'preselection_key'    => null,
            'volunteers'          => [],
            'excluded_volunteers' => [],
            'nivols'              => [],
            'structures_global'   => [],
            'structures_local'    => [],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'test_on_me'          => false,
        ], $defaults);
    }

    static public function getAudienceFormData(Request $request)
    {
        // Audience type can be located anywhere in the main form, so we need to seek for the
        // audience data following the path created using its full name.
        $name = trim(str_replace(['[', ']'], '.', trim($request->query->get('name'))), '.');
        $name = preg_replace('/\.+/', '.', $name);

        $data = $request->request->all();
        $path = array_filter(explode('.', $name));

        foreach ($path as $node) {
            $data = $data[$node];
        }

        foreach ($data as $key => $value) {
            if (in_array($key, AudienceType::LISTS)) {
                $data[$key] = self::split($value);
            }
        }

        return $data;
    }

    static public function split(string $value)
    {
        return array_unique(array_filter(preg_split('/[^0-9a-z*]/ui', $value)));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $structures      = $this->userManager->findForCurrentUser()->getStructures();
        $hasOneStructure = 1 === $structures->count();

        $builder
            ->add('preselection_key', TextType::class, [
                'label' => false,
            ])
            ->add('volunteers', TextType::class, [
                'label' => 'audience.search_for_volunteers',
            ])
            ->add('excluded_volunteers', TextType::class, [
                'label' => false,
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => 'audience.copy_paste_details',
                'required' => false,
                'attr'     => [
                    'rows' => 4,
                ],
            ])
            ->add('structures_global', TextType::class, [
                'data'  => $hasOneStructure ? [$structures->first()->getId()] : null,
                'label' => false,
            ])
            ->add('structures_local', TextType::class, [
                'label' => false,
            ])
            ->add('badges_all', CheckboxType::class, [
                'required' => false,
                'label'    => false,
            ])
            ->add('badges_ticked', TextType::class, [
                'label' => false,
            ])
            ->add('badges_searched', TextType::class, [
                'label'    => 'audience.search_other_badge',
                'required' => false,
            ])
            ->add('test_on_me', CheckboxType::class, [
                'label'    => false,
                'required' => false,
            ]);

        $lists = self::LISTS;
        foreach ($lists as $list) {
            $builder->get($list)->addModelTransformer(new CallbackTransformer(
                function (?array $fromModel) {
                    return $fromModel ? implode(',', $fromModel) : null;
                },
                function (?string $fromView) {
                    return $fromView ? self::split($fromView) : null;
                }
            ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Volunteer view
        $view->vars['volunteers_data'] = [];
        if ($ids = $form->get('volunteers')->getData()) {
            $view->vars['volunteers_data'] = array_values($this->audienceManager->getVolunteerList($ids));
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
        $view->vars['init_data']      = $data;

        $view->vars['preselection'] = [];
        if ($data['preselection_key']) {
            $view->vars['preselection'] = $this->expirableManager->get($data['preselection_key']);
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
            if ($hierarchy[$child] ?? false) {
                $ids = array_merge($ids, $this->findDescendants($hierarchy, $hierarchy[$child]));
            }
        }

        return $ids;
    }
}
