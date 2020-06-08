<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use App\Tools\PhoneNumberParser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class VolunteerType extends AbstractType
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('nivol', TextType::class, [
                'label' => 'manage_volunteers.form.nivol',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'manage_volunteers.form.first_name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'manage_volunteers.form.last_name',
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'manage_volunteers.form.phone_number',
                'required' => false,
            ])
            ->add('phoneNumberOptin', CheckboxType::class, [
                'label' => 'manage_volunteers.form.phone_number_optin',
                'required' => false,
            ])
        ;

        if (getenv('IS_REDCROSS')) {
            $builder
                ->add('phoneNumberLocked', CheckboxType::class, [
                    'label' => 'manage_volunteers.form.phone_number_locked',
                    'required' => false,
                ]);
        }

        $builder
            ->add('email', TextType::class, [
                'label' => 'manage_volunteers.form.email',
                'required' => false,
            ])
            ->add('emailOptin', CheckboxType::class, [
                'label' => 'manage_volunteers.form.email_optin',
                'required' => false,
            ])
        ;

        if (getenv('IS_REDCROSS')) {
            $builder
                ->add('emailLocked', CheckboxType::class, [
                    'label' => 'manage_volunteers.form.email_locked',
                    'required' => false,
                ]);
        }

        $builder
            ->add('minor', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.minor',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.enabled',
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class'        => Tag::class,
                'choice_label' => function ($tag) {
                    return sprintf('tag.%s', $tag);
                },
                'expanded'     => true,
                'multiple'     => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
                'attr'  => [
                    'class' => 'btn btn-primary',
                    'data-timeout-disabled' => '30000'
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $builder   = $event->getForm();

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $currentUser = $this->security->getUser();

                $builder
                    ->add('structures', EntityType::class, [
                        'label'         => 'manage_volunteers.form.structures',
                        'class'         => Structure::class,
                        'query_builder' => function (StructureRepository $er) use ($currentUser) {
                            return $er->getStructuresForUserQueryBuilder($currentUser);
                        },
                        'choice_label'  => function (Structure $structure) {
                            return $structure->getName();
                        },
                        'multiple'      => true,
                        'expanded'      => true,
                    ]);
            }
        });
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

