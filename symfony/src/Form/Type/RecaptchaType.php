<?php

namespace App\Form\Type;

use App\Captcha\CaptchaVerifierInterface;
use App\Captcha\CheckboxCaptchaVerifier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecaptchaType extends AbstractType
{
    private string                   $siteKey;
    private CaptchaVerifierInterface $verifier;

    public function __construct(string $siteKey, CaptchaVerifierInterface $verifier)
    {
        $this->siteKey  = $siteKey;
        $this->verifier = $verifier;
    }

    public function buildView(FormView $view, FormInterface $form, array $options) : void
    {
        $view->vars['site_key']           = $this->siteKey;
        $view->vars['mode']               = $this->verifier->getWidgetMode();
        $view->vars['checkbox_field_name'] = CheckboxCaptchaVerifier::FIELD_NAME;
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'mapped'   => false,
            'required' => false,
        ]);
    }

    public function getParent() : string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix() : string
    {
        return 'recaptcha';
    }
}
