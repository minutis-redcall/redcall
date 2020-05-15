<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Manager\StructureManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureType extends AbstractType
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'structure.form.name'
            ])
            ->add('parentStructure', StructureWidgetType::class, [
                'required' => false,
                'label' => 'structure.form.parent'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'button.submit'
            ]);

        $builder->get('parentStructure')->addModelTransformer(new CallbackTransformer(
            function (?Structure $fromBase) {
                return $fromBase ? $fromBase->getId() : null;
            },
            function ($fromForm) {
                return $fromForm ? $this->structureManager->find($fromForm) : null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Structure::class]);
    }
}