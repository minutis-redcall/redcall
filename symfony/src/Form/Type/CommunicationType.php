<?php

namespace App\Form\Type;

use App\Entity\Communication;
use App\Entity\Structure;
use App\Form\Model\Communication as CommunicationModel;
use App\Manager\StructureManager;
use App\Manager\UserInformationManager;
use App\Repository\StructureRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunicationType extends AbstractType
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param StructureManager       $structureManager
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(StructureManager $structureManager, UserInformationManager $userInformationManager)
    {
        $this->structureManager       = $structureManager;
        $this->userInformationManager = $userInformationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $currentUser     = $this->userInformationManager->findForCurrentUser();
        $volunteerCounts = $this->structureManager->countVolunteersInStructures($currentUser->getStructures()->toArray());

        $builder
            ->add('type', ChoiceType::class, [
                'label'    => false,
                'choices'  => [
                    'form.communication.fields.type_sms'   => Communication::TYPE_SMS,
                    'form.communication.fields.type_email' => Communication::TYPE_EMAIL,
                ],
                'expanded' => true,
            ])
            ->add('multipleAnswer', CheckboxType::class, [
                'label'    => 'form.communication.fields.multiple_answer',
                'required' => false,
            ])
            ->add('structures', EntityType::class, [
                'label'         => false,
                'class'         => Structure::class,
                'query_builder' => function (StructureRepository $er) use ($currentUser) {
                    return $er->getStructuresForUserQueryBuilder($currentUser);
                },
                'choice_label'  => function (Structure $structure) use ($volunteerCounts) {
                    return sprintf('%s (%s)', $structure->getName(), $volunteerCounts[$structure->getId()] ?? 0);
                },
                'multiple'      => true,
                'expanded'      => true,
            ])
            ->add('volunteers', VolunteersType::class, [
                'error_mapping' => [
                    '.' => 'volunteers',
                ],
            ])
            ->add('subject', TextType::class, [
                'label'    => 'form.communication.fields.subject',
                'required' => false,
            ])
            ->add('message', TextareaType::class, [
                'label' => 'form.communication.fields.body',
            ])
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
            ->add('prefix', TextType::class, [
                'label'    => 'form.communication.fields.prefix',
                'required' => false,
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
            'type'       => Communication::TYPE_SMS,
            'submit'     => true,
        ]);
    }
}