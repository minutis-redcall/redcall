<?php

namespace App\Form\Type;

use App\Manager\LanguageManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotNull;

class LanguageType extends AbstractType
{
    /**
     * @var LanguageManager
     */
    private $languageManager;

    public function __construct(LanguageManager $languageManager)
    {
        $this->languageManager = $languageManager;
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'       => 'form.communication.fields.language',
            'choices'     => $choices = $this->languageManager->getAvailableLanguageChoices(),
            'constraints' => [
                new NotNull(),
                new Choice(array_values($choices)),
            ],
        ]);
    }
}