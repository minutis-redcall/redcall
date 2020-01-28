<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use Bundles\PasswordLoginBundle\Base\BaseType;
use Bundles\PasswordLoginBundle\Entity\Captcha;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Validator\Constraints;

class ConnectType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', Type\EmailType::class, [
                'label'       => 'password_login.connect.email',
                'required'    => true,
                'constraints' => [
                    new Constraints\Length(['min' => 8]),
                ],
            ])
            ->add('password', Type\PasswordType::class, [
                'label'       => 'password_login.connect.password',
                'constraints' => new Constraints\Length([
                    'min' => 8,
                    'max' => BCryptPasswordEncoder::MAX_PASSWORD_LENGTH,
                ]),
            ]);

        $ip = $this->get('request_stack')->getMasterRequest()->getClientIp();
        if (!$this->getManager(Captcha::class)->isAllowed($ip)) {
            $builder->add('recaptcha', EWZRecaptchaType::class, [
                'label'       => 'password_login.connect.captcha',
                'constraints' => [
                    new RecaptchaTrue(),
                ],
            ]);
        }

        $builder
            ->add('submit', Type\SubmitType::class, [
                'label' => 'password_login.connect.connect',
            ]);
    }
}
