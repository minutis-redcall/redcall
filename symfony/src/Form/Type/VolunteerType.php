<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use App\Security\Helper\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThan;

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
            ->add('externalId', TextType::class, [
                'label' => 'manage_volunteers.form.external_id',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'manage_volunteers.form.first_name',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'manage_volunteers.form.last_name',
            ])
            ->add('birthday', BirthdayType::class, [
                'label'       => 'manage_volunteers.form.birthday',
                'widget'      => 'single_text',
                'required'    => false,
                'constraints' => [
                    new LessThan('today'),
                ],
            ])
            ->add('phones', PhoneCardsType::class, [
                'label' => false,
            ])
            ->add('phoneNumberOptin', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.phone_number_optin',
                'required' => false,
            ])
            ->add('phoneNumberLocked', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.phone_number_locked',
                'required' => false,
            ])
            ->add('onlyOutboundSms', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.phone_number_only_outbound',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label'    => 'manage_volunteers.form.email',
                'required' => false,
            ])
            ->add('emailOptin', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.email_optin',
                'required' => false,
            ])
            ->add('emailLocked', CheckboxType::class, [
                'label'    => 'manage_volunteers.form.email_locked',
                'required' => false,
            ])
            ->add('optoutUntil', DateType::class, [
                'label'       => 'manage_volunteers.form.optout_until',
                'widget'      => 'single_text',
                'required'    => false,
                'constraints' => [
                    new GreaterThan('tomorrow'),
                ],
            ])
            ->add('badges', BadgeSelectionType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
                'attr'  => [
                    'class'                 => 'btn btn-primary',
                    'data-timeout-disabled' => '30000',
                ],
            ]);

        $builder->get('externalId')->addModelTransformer(new CallbackTransformer(
            function ($fromBase) {
                return $fromBase;
            },
            function ($fromForm) {
                return strtoupper(ltrim($fromForm, '0'));
            }
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $builder = $event->getForm();

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $currentUser = $this->security->getUser();

                $builder
                    ->add('structures', EntityType::class, [
                        'label'         => 'manage_volunteers.form.structures',
                        'class'         => Structure::class,
                        'query_builder' => function (StructureRepository $er) use ($currentUser) {
                            return $er->getStructuresForUserQueryBuilder($this->security->getPlatform(), $currentUser);
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
