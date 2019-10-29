<?php

namespace App\Form\Type;

use App\Entity\Organization;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Repository\TagRepository;
use App\Tools\PhoneNumberParser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VolunteerType extends AbstractType
{
    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('organization', EntityType::class, [
                'label'    => 'manage_volunteers.form.organization',
                'class' => Organization::class,
                'choice_label' => 'name',
            ])
            ->add('nivol', TextType::class, [
                'label'    => 'manage_volunteers.form.nivol',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'manage_volunteers.form.first_name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'manage_volunteers.form.first_name',
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'manage_volunteers.form.phone_number',
            ])
            ->add('email', TextType::class, [
                'label' => 'manage_volunteers.form.email',
            ])
            ->add('minor', CheckboxType::class, [
                'label' => 'manage_volunteers.form.minor',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'manage_volunteers.form.enabled',
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => function($tag) {
                    return sprintf('tag.%s', $tag);
                },
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
            ]);

        $builder->get('phoneNumber')->addModelTransformer(new CallbackTransformer(
            function ($fromBase) {
                return $fromBase;
            },
            function ($fromForm) {
                return PhoneNumberParser::parse($fromForm);
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Volunteer::class,
        ]);
    }
}

