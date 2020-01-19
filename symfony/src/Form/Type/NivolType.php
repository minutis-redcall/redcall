<?php

namespace App\Form\Type;

use App\Manager\VolunteerManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolType extends TextType
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param VolunteerManager    $volunteerManager
     * @param TranslatorInterface $translator
     */
    public function __construct(VolunteerManager $volunteerManager, TranslatorInterface $translator)
    {
        $this->volunteerManager = $volunteerManager;
        $this->translator       = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'nivol';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $volunteer          = $this->volunteerManager->findOneByNivol($view->vars['value']);
            $view->vars['data'] = [$volunteer->toSearchResults($this->translator)];
        }
    }
}