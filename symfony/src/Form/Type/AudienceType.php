<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AudienceType extends AbstractType
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(UserManager $userManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        TagManager $tagManager,
        TranslatorInterface $translator)
    {
        $this->userManager      = $userManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->tagManager       = $tagManager;
        $this->translator       = $translator;
    }

    /**
     * This transformer is also used in the audience widget
     *
     * @param array|null $formData
     *
     * @return array
     */
    static public function getNivolsFromFormData(?array $formData)
    {
        if (!$formData) {
            return [];
        }

        $nivols = [];

        if ($formData['nivols'] ?? false) {
            $nivols = array_merge($nivols, array_unique(array_filter(preg_split('/[^0-9a-z*]/ui', $formData['nivols']))));
        }

        foreach ($formData['structures'] ?? [] as $structure) {
            /** @var Structure $nivols */
            $nivols = array_merge($nivols, explode(',', $formData[sprintf('structure-%d', $structure->getId())]));
        }

        $nivols = array_map(function (string $nivol) {
            return strtoupper(ltrim($nivol, '0'));
        }, $nivols);

        return array_filter(array_unique($nivols));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $structures = $this->userManager->findForCurrentUser()->getStructures()->toArray();

        $builder
            ->add('structures', EntityType::class, [
                'class'         => Structure::class,
                'query_builder' => $this->userManager->getCurrentUserStructuresQueryBuilder(),
                'choice_label'  => function (Structure $structure) {
                    return $structure->getDisplayName();
                },
                'multiple'      => true,
                'expanded'      => true,
            ])
            ->add('tags', EntityType::class, [
                'class'         => Tag::class,
                'query_builder' => function (TagRepository $tagRepository) {
                    return $tagRepository->findAllQueryBuilder();
                },
                'choice_label'  => function (Tag $tag) {
                    return $this->translator->trans(sprintf('tag.%s', $tag->getLabel()));
                },
                'multiple'      => true,
                'expanded'      => true,
            ])
            ->add('nivols', TextareaType::class, [
                'label'    => false,
                'required' => false,
            ]);;

        // Every structure has its own list of volunteers
        $structuresById = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $builder->add(sprintf('structure-%d', $structure->getId()), TextType::class, [
                'required' => false,
            ]);

            $structuresById[$structure->getId()] = $structure;
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function (?array $nivols) use ($structuresById) {
                if (!$nivols) {
                    return [];
                }

                $formData = [
                    'structures' => [],
                ];

                $byStructure = $this->volunteerManager->organizeNivolsByStructures($structuresById, $nivols);
                foreach ($byStructure as $structureId => $list) {
                    // Populate nivols field
                    if (!$structureId) {
                        $formData['nivols'] = implode(',', $list);
                        continue;
                    }

                    // Populate structure datalist
                    $key            = sprintf('structure-%d', $structureId);
                    $formData[$key] = implode(',', $list);

                    // Populate structure ticks
                    if (!in_array($structuresById[$structureId], $formData['structures'])) {
                        $formData['structures'][] = $structuresById[$structureId];
                    }
                }

                return $formData;
            },
            function (?array $formData) {
                return self::getNivolsFromFormData($formData);
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $structures = [];
        foreach ($this->userManager->getCurrentUserStructures() as $structure) {
            /** @var Structure $structure */
            $structures[$structure->getId()] = $structure;
        }

        $view->vars['structures']       = $structures;
        $view->vars['root_structures']  = $this->userManager->findForCurrentUser()->getRootStructures();
        $view->vars['volunteer_counts'] = $this->structureManager->getVolunteerCountByStructuresForCurrentUser();
        $view->vars['tag_counts']       = $this->structureManager->getTagCountByStructuresForCurrentUser();
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

    public function getBlockPrefix(): string
    {
        return 'audience';
    }
}