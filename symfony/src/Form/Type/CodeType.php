<?php

namespace App\Form\Type;

use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CodeType extends AbstractType
{
    private CaptchaManager $captchaManager;
    private RequestStack   $requestStack;

    public function __construct(CaptchaManager $captchaManager, RequestStack $requestStack)
    {
        $this->captchaManager = $captchaManager;
        $this->requestStack   = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, [
                'label'       => 'nivol_auth.input_code',
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6, 'max' => 6]),
                ],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label'    => 'nivol_auth.remember_me',
                'required' => false,
            ]);

        $ip = $this->requestStack->getMainRequest()->getClientIp();

        if (!$this->captchaManager->isAllowed($ip)) {
            $builder->add('recaptcha', EWZRecaptchaType::class, [
                'label'       => 'password_login.connect.captcha',
                'constraints' => [
                    new RecaptchaTrue(),
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'nivol_auth.input_connect',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_extra_fields', true);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
