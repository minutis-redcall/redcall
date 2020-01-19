<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Manager\StructureManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class StructureListType extends AbstractType
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @param StructureManager $structureManager
     */
    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
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
        $builder
            ->add('structures', TextType::class, [
                'required' => false,
            ]);

        $builder->get('structures')
                ->addModelTransformer(new CallbackTransformer(
                    function (?array $structuresAsEntities) {
                        return array_map(function (Structure $structure) {
                            return $structure->getId();
                        }, $structuresAsEntities);
                    },
                    function (?string $structuresAsList) {
                        return array_filter(array_map(function (int $structureId) {
                            return $this->structureManager->find($structureId);
                        }, explode(',', strval($structuresAsList))));
                    }
                ));
    }
}