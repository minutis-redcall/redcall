<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use Bundles\PasswordLoginBundle\Base\BaseType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class ForgotPasswordType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', Type\EmailType::class, [
                'label'       => 'password_login.forgot_password.username',
                'required'    => true,
                'constraints' => [
                    new Constraints\Length(['min' => 8]),
                    new Constraints\Email(),
                ],
            ]);

        //        $ip = $this->get('request_stack')->getMasterRequest()->getClientIp();
        //        if (!$this->getManager(Captcha::class)->isAllowed($ip)) {
        //            $builder->add('recaptcha', EWZRecaptchaType::class, [
        //                'label'       => 'password_login.forgot_password.captcha',
        //                'constraints' => [
        //                    new RecaptchaTrue(),
        //                ],
        //            ]);
        //        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'password_login.forgot_password.submit',
        ]);
    }
}
