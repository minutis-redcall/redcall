<?php

namespace App\Form\Extension;

use App\Entity\User;
use App\Manager\PlatformConfigManager;
use Bundles\PasswordLoginBundle\Form\Type\RegistrationType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotNull;

class RegistrationTypeExtension extends AbstractTypeExtension
{
    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    public function __construct(PlatformConfigManager $platformManager)
    {
        $this->platformManager = $platformManager;
    }

    public static function getExtendedTypes() : iterable
    {
        return [
            RegistrationType::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $this->platformManager->getPlatformChoices();

        $builder->add('platform', ChoiceType::class, [
            'label'       => 'security.registration.platform',
            'expanded'    => true,
            'choices'     => $choices,
            'constraints' => [
                new NotNull(),
                new Choice(['choices' => $choices]),
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();

            if ($user->getPlatform()) {
                $platform = $this->platformManager->getPlaform($user->getPlatform());

                $user->setLocale($platform->getDefaultLanguage()->getLocale());
                $user->setTimezone($platform->getTimezone());
            }
        });
    }
}