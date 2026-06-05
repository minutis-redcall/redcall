<?php

namespace Bundles\PasswordLoginBundle\Form\Type;

use App\Form\Type\RecaptchaType;
use App\Validator\Constraints\RecaptchaTrue;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ForgotPasswordType extends AbstractType
{
    /**
     * @var CaptchaManager
     */
    private $captchaManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(CaptchaManager $captchaManager, RequestStack $requestStack)
    {
        $this->captchaManager = $captchaManager;
        $this->requestStack   = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', Type\EmailType::class, [
                'label'       => 'password_login.forgot_password.username',
                'required'    => true,
                'attr'        => [
                    'autocomplete' => 'username',
                    'autofocus'    => 'autofocus',
                    'inputmode'    => 'email',
                ],
                'constraints' => [
                    new Constraints\Length(min: 8),
                    new Constraints\Email(),
                ],
            ]);

        $ip = $this->requestStack->getMainRequest()->getClientIp();

        if (!$this->captchaManager->isAllowed($ip)) {
            $builder->add('recaptcha', RecaptchaType::class, [
                'label'       => 'password_login.forgot_password.captcha',
                'constraints' => [
                    new RecaptchaTrue(),
                ],
            ]);
        }

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'password_login.forgot_password.submit',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('allow_extra_fields', true);
    }
}
