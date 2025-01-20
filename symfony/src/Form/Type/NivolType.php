<?php

namespace App\Form\Type;

use App\Manager\NivolManager;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolType extends AbstractType
{
    private NivolManager        $nivolManager;
    private RouterInterface     $router;
    private TranslatorInterface $translator;
    private CaptchaManager      $captchaManager;
    private RequestStack        $requestStack;

    public function __construct(NivolManager $nivolManager,
        RouterInterface $router,
        TranslatorInterface $translator,
        CaptchaManager $captchaManager,
        RequestStack $requestStack)
    {
        $this->nivolManager   = $nivolManager;
        $this->router         = $router;
        $this->translator     = $translator;
        $this->captchaManager = $captchaManager;
        $this->requestStack   = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setAction(
                $this->router->generate('nivol')
            )
            ->add('nivol', TextType::class, [
                'label'       => 'nivol_auth.input_nivol',
                'required'    => true,
                'constraints' => [
                    new Callback([
                        'callback' => function ($nivol, $context) {
                            if ($nivol && !$this->nivolManager->getUserByNivol($nivol)) {
                                $context
                                    ->buildViolation($this->translator->trans('nivol_auth.nivol_not_found'))
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ]);

        $ip = $this->requestStack->getMainRequest()->getClientIp();

        if ($ip === '169.155.250.88' /* test alain */ || !$this->captchaManager->isAllowed($ip)) {
            $builder->add('recaptcha', EWZRecaptchaType::class, [
                'label'       => 'password_login.connect.captcha',
                'constraints' => [
                    new RecaptchaTrue(),
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'nivol_auth.input_submit',
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
