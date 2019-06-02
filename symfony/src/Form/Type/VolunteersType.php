<?php

namespace App\Form\Type;

use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Repository\TagRepository;
use App\Repository\VolunteerRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

class VolunteersType extends AbstractType
{
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * VolunteersType constructor.
     *
     * @param VolunteerRepository $volunteerRepository
     * @param TagRepository       $tagRepository
     * @param TranslatorInterface $translator
     */
    public function __construct(VolunteerRepository $volunteerRepository,
        TagRepository $tagRepository,
        TranslatorInterface $translator)
    {
        $this->volunteerRepository = $volunteerRepository;
        $this->tagRepository       = $tagRepository;
        $this->translator          = $translator;
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
        $tagCounts = $this->volunteerRepository->getVolunteersCountByTags();

        $builder
            ->add('tags', EntityType::class, [
                'class'         => Tag::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                              ->orderBy('t.id', 'asc');
                },
                'choice_label'  => function (Tag $tag) use ($tagCounts) {
                    return sprintf('%s (%d)',
                        $this->translator->trans(sprintf('tag.%s', $tag->getLabel())),
                        $tagCounts[$tag->getId()] ?? 0
                    );
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
                        return $this->volunteerRepository->find($volunteerId);
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
        $view->vars['volunteers'] = array_map(function (Volunteer $volunteer) {
            return [
                'id'         => strval($volunteer->getId()),
                'firstName'  => $volunteer->getFirstName(),
                'lastName'   => $volunteer->getLastName(),
                'tagIds'     => array_map(function (Tag $tag) {
                    return $tag->getId();
                }, $volunteer->getTags()->toArray()),
                'tagLabels'  => sprintf('(%s)', implode(', ', array_map(function (Tag $tag) {
                    return $this->translator->trans(sprintf('tag.shortcuts.%s', $tag->getLabel()));
                }, $volunteer->getTagsView()))),
            ];
        }, $this->volunteerRepository->findAllEnabledVolunteers());
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
