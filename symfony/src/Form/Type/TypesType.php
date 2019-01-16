<?php

namespace App\Form\Type;

use App\Entity\Campaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TypesType
 *
 * Represents a choice list of types (green, orange, red...)
 */
class TypesType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TypesType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'      => $choices = array_combine(Campaign::TYPES, Campaign::TYPES),
            'choice_label' => function ($choice, $key, $value) {
                return $this->translator->trans('campaign.types.'.$value);
            },
            'expanded'     => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'types';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}