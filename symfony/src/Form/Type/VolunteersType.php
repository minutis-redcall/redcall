<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class VolunteersType extends AbstractType
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param VolunteerManager       $volunteerManager
     * @param StructureManager       $structureManager
     * @param UserInformationManager $userInformationManager
     * @param TranslatorInterface    $translator
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        UserInformationManager $userInformationManager,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage)
    {
        $this->volunteerManager       = $volunteerManager;
        $this->structureManager       = $structureManager;
        $this->userInformationManager = $userInformationManager;
        $this->translator             = $translator;
        $this->tokenStorage           = $tokenStorage;
    }

    /**
     * Transforms the model's list of volunteers into a string of volunteers id,
     * as volunteers are visually a list of tags. And the reverse when form is
     * submitted.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tags', EntityType::class, [
                'class'         => Tag::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                              ->orderBy('t.id', 'asc');
                },
                'choice_label'  => function (Tag $tag) {
                    return $this->translator->trans(sprintf('tag.%s', $tag->getLabel()));
                },
                'multiple'      => true,
                'expanded'      => true,
                'mapped'        => false,
            ])
            ->add('volunteers', TextType::class, [
                'required' => false,
            ])
            ->addModelTransformer(new CallbackTransformer(
                function (?array $volunteersAsArray) {
                    return [
                        'tags'       => [],
                        'volunteers' => implode(',', array_map(function (Volunteer $volunteer) {
                            return $volunteer->getId();
                        }, $volunteersAsArray ?? [])),
                    ];
                },
                function (?array $volunteersAsIds) {
                    return array_filter(array_map(function ($volunteerId) {
                        if ($volunteerId) {
                            return $this->volunteerManager->find($volunteerId);
                        }
                    }, explode(',', strval($volunteersAsIds['volunteers']))));
                }
            ));
    }

    /**
     * This type requires to have a list of tickboxes containing jobs,
     * and a list of tags containing volunteers. As everything will be
     * handled in JS, we just need to preload the database of volunteers
     * and the list of tags.
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $structures               = $this->userInformationManager->findForCurrentUser()->getStructures()->toArray();
        $view->vars['structures'] = $this->structureManager->getTagCountByStructuresForCurrentUser();

        $view->vars['volunteers'] = array_map(function (Volunteer $volunteer) use ($structures) {
            return [
                'id'           => strval($volunteer->getId()),
                'firstName'    => $volunteer->getFirstName(),
                'lastName'     => $volunteer->getLastName(),
                'tagIds'       => array_map(function (Tag $tag) {
                    return $tag->getId();
                }, $volunteer->getTags()->toArray()),
                'structureIds' => array_map(function (Structure $tag) {
                    return $tag->getId();
                }, $volunteer->getStructures()->toArray()),
                'tagLabels'    => $volunteer->getTagsView() ? sprintf('(%s)', implode(', ', array_map(function (Tag $tag) {
                    return $this->translator->trans(sprintf('tag.shortcuts.%s', $tag->getLabel()));
                }, $volunteer->getTagsView()))) : '',
                'structures'   => array_filter(array_map(function (Structure $structure) use ($volunteer) {
                    if ($volunteer->getStructures()->contains($structure)) {
                        return $structure->getId();
                    }
                }, $structures)),
            ];
        }, $this->volunteerManager->findCallableForUser(
            $this->tokenStorage->getToken()->getUser()
        ));
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return FormType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'volunteers';
    }
}
