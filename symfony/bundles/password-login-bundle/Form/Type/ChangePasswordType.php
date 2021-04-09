<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', Type\RepeatedType::class, [
                'type'            => Type\PasswordType::class,
                'invalid_message' => $this->translator->trans('password_login.change_password.password_should_match'),
                'required'        => true,
                'first_options'   => [
                    'label'       => 'password_login.change_password.password',
                    'constraints' => [
                        new Constraints\Length([
                            'min' => 8,
                            'max' => 4096,
                        ]),
                        new Constraints\NotCompromisedPassword(),
                    ],
                ],
                'second_options'  => ['label' => 'password_login.change_password.repeat_password'],
            ])
            ->add('submit', Type\SubmitType::class, [
                'label' => 'password_login.change_password.submit',
            ]);
    }
}
