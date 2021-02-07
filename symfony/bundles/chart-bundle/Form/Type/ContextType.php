<?php

namespace Bundles\ChartBundle\Form\Type;

use Bundles\ChartBundle\Context\Bag\FormatBag;
use Bundles\ChartBundle\Context\Format\FormatInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'attr'        => [
                    'class' => 'context-type-selector',
                ],
            ]);

        foreach ($this->formatBag->getFormats() as $format) {
            /** @var FormatInterface $format */
            $builder->add($format->getName(), $format->getFormType(), [
                'attr' => [
                    'class' => sprintf('context-type context-type-%s', $format->getName()),
                ],
            ]);
        }

        // We only want constraints on the selected type.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($builder) {
            foreach ($this->formatBag->getFormats() as $format) {
                if ($format->getName() !== $event->getData()['type']) {
                    $event->getForm()->add($format->getName(), $format->getFormType(), [
                        'attr'              => [
                            'class' => sprintf('context-type context-type-%s', $format->getName()),
                        ],
                        'validation_groups' => false,
                    ]);
                }
            }
        });
    }

    public function getBlockPrefix()
    {
        return 'context';
    }
}