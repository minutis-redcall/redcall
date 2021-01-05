<?php

namespace Bundles\ChartBundle\Form\Type;

use Bundles\ChartBundle\Context\Bag\FormatBag;
use Bundles\ChartBundle\Context\Format\FormatInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContextType extends AbstractType
{
    /**
     * @var FormatBag
     */
    private $formatBag;

    public function __construct(FormatBag $formatBag)
    {
        $this->formatBag = $formatBag;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types = array_flip(array_map(function (FormatInterface $format) {
            return $format->getTranslationKey();
        }, $this->formatBag->getFormats()));

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

        foreach ($this->formatBag->getFormats() as $format) {
            /** @var FormatInterface $format */
            $builder->add($format->getName(), $format->getFormType(), [
                'required' => false,
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'context';
    }
}