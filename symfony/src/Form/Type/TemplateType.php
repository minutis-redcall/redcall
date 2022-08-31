<?php

namespace App\Form\Type;

use App\Entity\Communication;
use App\Entity\Template;
use App\Manager\TemplateImageManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TemplateType extends AbstractType
{
    /**
     * @var TemplateImageManager
     */
    private $templateImageManager;

    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    public function __construct(TemplateImageManager $templateImageManager, \HTMLPurifier $purifier)
    {
        $this->templateImageManager = $templateImageManager;
        $this->purifier             = $purifier;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => 'manage_structures.templates.form.name',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'       => 'manage_structures.templates.form.type',
                'choices'     => [
                    'manage_structures.templates.types.sms'   => Communication::TYPE_SMS,
                    'manage_structures.templates.types.call'  => Communication::TYPE_CALL,
                    'manage_structures.templates.types.email' => Communication::TYPE_EMAIL,
                ],
                'constraints' => [
                    new Choice(Communication::TYPES),
                ],
                'expanded'    => true,
            ])
            ->add('language', LanguageType::class)
            ->add('subject', TextType::class, [
                'label'       => 'manage_structures.templates.form.subject',
                'required'    => false,
                'constraints' => [
                    new Length([
                        'max' => 80,
                    ]),
                ],
            ])
            ->add('body_text', TextareaType::class, [
                'label'    => 'manage_structures.templates.form.body',
                'required' => false,
                'mapped'   => false,
            ])
            ->add('body_html', TextareaType::class, [
                'label'    => 'manage_structures.templates.form.body',
                'required' => false,
                'mapped'   => false,
            ])
            ->add('answers', CollectionType::class, [
                'label'         => 'manage_structures.templates.form.answers',
                'entry_type'    => AnswerType::class,
                'entry_options' => [
                    'label'       => false,
                    'constraints' => [
                        new Length([
                            'max' => 255,
                        ]),
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
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
                'attr'  => [
                    'class' => 'btn btn-primary',
                ],
            ]);

        // Maps the body property to either body_text and body_Html form field
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var Template $template */
            $template = $event->getData();
            if (!$template) {
                return;
            }

            $form = $event->getForm();
            if (Communication::isPlaintext($template->getType())) {
                $form->get('body_text')->setData($template->getBody());
            } else {
                $form->get('body_html')->setData($template->getBodyWithImages());
            }
        });

        // Maps either body_text or body_html to the body property according to the
        // selected message type
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Template $template */
            $template = $event->getData();
            if (!$template) {
                return;
            }

            $form = $event->getForm();
            if (Communication::isPlaintext($template->getType())) {
                $template->setBody(
                    strip_tags($form->get('body_text')->getData())
                );
            } else {
                $body = $this->templateImageManager->handleImages(
                    $template,
                    $form->get('body_html')->getData()
                );

                $template->setBody(
                    $this->purifier->purify($body)
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Template::class]);
    }
}