<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserInformationManager;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(UserInformationManager $userInformationManager, StructureManager $structureManager, TagManager $tagManager, TranslatorInterface $translator)
    {
        $this->userInformationManager = $userInformationManager;
        $this->structureManager = $structureManager;
        $this->tagManager = $tagManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $structures = $this->userInformationManager->findForCurrentUser()->getStructures()->toArray();

        $builder
            ->add('structures', EntityType::class, [
                'class' => Structure::class,
                'query_builder' => $this->userInformationManager->getCurrentUserStructuresQueryBuilder(),
                'choice_label' => function (Structure $structure) {
                    return $structure->getDisplayName();
                },
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'query_builder' => function (TagRepository $tagRepository) {
                    return $tagRepository->findAllQueryBuilder();
                },
                'choice_label' => function (Tag $tag) {
                    return $this->translator->trans(sprintf('tag.%s', $tag->getLabel()));
                },
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('nivols', TextareaType::class, [
                'label' => false,
                'required' => false,
            ]);
        ;

        // Every structure has its own list of volunteers
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            $builder->add(sprintf('structure-%d', $structure->getId()), TextType::class, [
                'required' => false,
            ]);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['structures'] = $this->userInformationManager->getCurrentUserStructures();
        $view->vars['volunteer_counts'] = $this->structureManager->getVolunteerCountByStructuresForCurrentUser();
        $view->vars['tag_counts'] = $this->structureManager->getTagCountByStructuresForCurrentUser();


    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
        ]);
    }


    public function getBlockPrefix(): string
    {
        return 'audience';
    }
}