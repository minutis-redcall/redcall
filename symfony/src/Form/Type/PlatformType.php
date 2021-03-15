<?php

namespace App\Form\Type;

use App\Manager\PlatformConfigManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotNull;

class PlatformType extends AbstractType
{
    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    public function __construct(PlatformConfigManager $platformManager)
    {
        $this->platformManager = $platformManager;
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = $this->platformManager->getPlatformChoices();

        return $resolver->setDefaults([
            'expanded'    => true,
            'choices'     => $choices,
            'constraints' => [
                new NotNull(),
                new Choice(['choices' => $choices]),
            ],
        ]);
    }
}