<?php

namespace Bundles\ChartBundle\Form\Type;

use Bundles\ChartBundle\Bag\ContextTypeBag;
use Bundles\ChartBundle\Context\TypeEnum;
use Bundles\ChartBundle\ContextType\ContextTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContextType extends AbstractType
{
    /**
     * @var ContextTypeBag
     */
    private $contextTypeBag;

    public function __construct(ContextTypeBag $contextTypeBag)
    {
        $this->contextTypeBag = $contextTypeBag;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types = array_flip(array_map(function (ContextTypeInterface $contextType) {
            return $contextType->getTranslationKey();
        }, $this->contextTypeBag->getContextTypes()));

        $builder
            ->add('name', TextType::class, [
                'label'       => 'chart.context.name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'       => 'chart.context.type',
                'choices'     => $types,
                'constraints' => [
                    new NotBlank(),
                    new Choice([
                        'choices' => $types,
                    ]),
                ],
            ]);

        foreach ($this->contextTypeBag->getContextTypes() as $contextType) {
            /** @var ContextTypeInterface $contextType */
            $builder->add($contextType->getName(), $contextType->getFormType(), [
                'constraints' => null,
                'required'    => false,
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'context';
    }
}