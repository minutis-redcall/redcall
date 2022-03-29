<?php

namespace App\Form\Type;

use App\Entity\Choice;
use App\Entity\User;
use App\Form\Model\SmsTrigger;
use App\Security\Helper\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SmsTriggerType extends AbstractType
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

        /** @var User $user */
        $user      = $this->security->getUser();
        $shortcuts = array_combine($user->getStructuresShortcuts(), $user->getStructuresShortcuts());

        $builder
            ->add('audience', AudienceType::class)
            ->add('language', LanguageType::class)
            ->add('shortcut', ChoiceType::class, [
                'label'       => 'form.communication.fields.shortcut',
                'choices'     => $shortcuts,
                'required'    => false,
                'data'        => $shortcuts ? reset($shortcuts) : null,
                'constraints' => [
                    new ChoiceConstraint(['choices' => $shortcuts]),
                    new Callback(function ($value, ExecutionContextInterface $context, $payload) use ($shortcuts) {
                        if (null !== $value && !in_array($value, $shortcuts)) {
                            $context
                                ->buildViolation('')
                                ->atPath('shortcut')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'form.communication.fields.body',
                'required' => false,
            ])
            ->add('answers', CollectionType::class, [
                'label'         => 'form.communication.fields.answers',
                'entry_type'    => AnswerType::class,
                'entry_options' => [
                    'label' => false,
                    'attr'  => [
                        'maxlength' => Choice::MAX_LENGTH_SMS,
                    ],
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty'  => true,
                'prototype'     => true,
                'required'      => false,
                'attr'          => [
                    'class' => 'collection',
                ],
            ])
            ->add('multipleAnswer', CheckboxType::class, [
                'label'    => 'form.communication.fields.multiple_answer',
                'required' => false,
            ])
            ->add('test', SubmitType::class, [
                'label' => 'form.communication.fields.test',
                'attr'  => [
                    'class' => 'trigger-test btn-secondary',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.communication.fields.submit',
                'attr'  => [
                    'class' => 'trigger-launch btn-primary',
                ],
            ]);

        $builder->get('message')->addModelTransformer(new CallbackTransformer(
            function ($fromModel) {
                return $fromModel;
            },
            function ($fromView) {
                return strip_tags($fromView);
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmsTrigger::class,
            'submit'     => true,
            'attr'       => [
                'class'        => 'trigger',
                'autocomplete' => 'off',
            ],
        ]);
    }
}