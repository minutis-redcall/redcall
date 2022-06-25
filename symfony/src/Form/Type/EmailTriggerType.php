<?php

namespace App\Form\Type;

use App\Entity\Choice;
use App\Form\Model\EmailTrigger;
use App\Manager\MediaManager;
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

class EmailTriggerType extends AbstractType
{
    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    public function __construct(MediaManager $mediaManager, \HTMLPurifier $purifier)
    {
        $this->mediaManager = $mediaManager;
        $this->purifier     = $purifier;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('multipleAnswer', ChoiceType::class, [
                'label'    => 'form.communication.fields.multiple_answer',
                'expanded' => true,
                'choices'  => [
                    'form.communication.fields.multiple_answer_one'     => false,
                    'form.communication.fields.multiple_answer_several' => true,
                ],
            ])
            ->add('audience', AudienceType::class)
            ->add('language', LanguageType::class)
            ->add('subject', TextType::class, [
                'label'    => 'form.communication.fields.subject',
                'required' => false,
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
                        'maxlength' => Choice::MAX_LENGTH_DEFAULT,
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

        // Convert inline images into GCS images
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            if ($event->getData()) {
                $this->extractImages($event->getData());
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailTrigger::class,
            'submit'     => true,
            'attr'       => [
                'class'        => 'trigger',
                'autocomplete' => 'off',
            ],
        ]);
    }

    private function extractImages(EmailTrigger $trigger)
    {
        // We need to replace all inline images by a placeholder
        $matches = [];
        preg_match_all('|\<img src=\"data\:image\/(.[^\;]+)\;base64\,(.[^\"]+)\"\>|', $trigger->getMessage(), $matches);

        foreach (array_keys($matches[0]) as $index) {
            $binary = base64_decode($matches[2][$index]);
            if (!$binary) {
                continue;
            }

            // Sanitize image to png whatever its extension (sorry animated gifs)
            if (!$gd = @imagecreatefromstring($binary)) {
                continue;
            }
            imagesavealpha($gd, true);
            ob_start();
            imagepng($gd);
            $clean = ob_get_clean();

            // Store image on GCS
            $media = $this->mediaManager->createMedia('png', $clean);
            $trigger->addImage($media);

            // Replace HTML code by a placeholder
            $trigger->setMessage(
                str_replace(
                    $matches[0][$index],
                    sprintf('{image:%s}', $media->getUuid()),
                    $trigger->getMessage()
                )
            );
        }

        $trigger->setMessage(
            $this->purifier->purify($trigger->getMessage())
        );
    }
}