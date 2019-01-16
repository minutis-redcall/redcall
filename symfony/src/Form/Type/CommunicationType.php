<?php

namespace App\Form\Type;

use App\Entity\Communication;
use App\Form\Model\Communication as CommunicationModel;
use App\Repository\VolunteerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunicationType extends AbstractType
{
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * CommunicationType constructor.
     *
     * @param VolunteerRepository $volunteerRepository
     */
    public function __construct(VolunteerRepository $volunteerRepository)
    {
        $this->volunteerRepository = $volunteerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('type', ChoiceType::class, [
                'label'    => false,
                'choices'  => [
                    'form.communication.fields.type_alert' => Communication::TYPE_ALERT,
                    'form.communication.fields.type_web'   => Communication::TYPE_WEB,
                ],
                'expanded' => true,
            ])
            ->add('volunteers', VolunteersType::class, [
                'error_mapping' => [
                    '.' => 'volunteers',
                ],
            ])
            ->add('message', TextareaType::class)
            ->add('answers', CollectionType::class, [
                'label'         => 'form.communication.fields.answers',
                'entry_type'    => AnswerType::class,
                'entry_options' => [
                    'label' => false,
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
            ->add('geoLocation', CheckboxType::class, [
                'label'    => 'form.communication.fields.geo_location',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.communication.fields.submit',
                'attr'  => [
                    'class' => 'btn-primary',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CommunicationModel::class,
            'type'       => Communication::TYPE_ALERT,
            'submit'     => true,
        ]);
    }
}