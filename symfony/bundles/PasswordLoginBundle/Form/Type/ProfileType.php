<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use Bundles\PasswordLoginBundle\Base\BaseType;
use Bundles\PasswordLoginBundle\Entity\Captcha;
use Bundles\PasswordLoginBundle\Entity\User;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProfileType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('current_password', Type\PasswordType::class, [
                'required'    => true,
                'label'       => 'password_login.profile.current_password',
                'constraints' => [
                    new Constraints\Length([
                        'min' => 8,
                        'max' => BCryptPasswordEncoder::MAX_PASSWORD_LENGTH,
                    ]),
                    new Constraints\Callback([
                        'callback' => function ($object, ExecutionContextInterface $context, $payload) {
                            if (!$this->get('security.password_encoder')->isPasswordValid($this->getUser(), $object)) {
                                $context
                                    ->buildViolation($this->trans('password_login.profile.invalid_current_password'))
                                    ->atPath('current_password')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
                'mapped'      => false,
            ])
            ->add('username', Type\EmailType::class, [
                'label'       => 'password_login.profile.email',
                'required'    => true,
                'constraints' => [
                    new Constraints\Length(['min' => 8]),
                    new Constraints\Callback([
                        'callback' => function ($object, ExecutionContextInterface $context, $payload) {
                            if ($object !== $this->getUser()->getUsername()
                                && $this->getManager(User::class)->findOneByUsername($object)) {
                                $context
                                    ->buildViolation($this->trans('password_login.profile.already_exists'))
                                    ->atPath('username')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ])
            ->add('password', Type\RepeatedType::class, [
                'type'            => Type\PasswordType::class,
                'invalid_message' => $this->trans('password_login.profile.password_should_match'),
                'required'        => false,
                'first_options'   => [
                    'label'       => 'password_login.profile.password',
                    'constraints' => new Constraints\Length([
                        'min' => 8,
                        'max' => BCryptPasswordEncoder::MAX_PASSWORD_LENGTH,
                    ]),
                ],
                'second_options'  => ['label' => 'password_login.register.repeat_password'],
            ]);

        $ip = $this->get('request_stack')->getMasterRequest()->getClientIp();
        if (!$this->getManager(Captcha::class)->isGracePeriod($ip)) {
            $builder->add('recaptcha', EWZRecaptchaType::class, [
                'label'       => 'password_login.profile.captcha',
                'constraints' => [
                    new RecaptchaTrue(),
                ],
                'mapped'      => false,
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'password_login.profile.submit',
        ]);
    }
}
