<?php

namespace App\Form\Type;

use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class VolunteerWidgetType extends TextType
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(VolunteerManager $volunteerManager, Security $security, TranslatorInterface $translator)
    {
        $this->volunteerManager = $volunteerManager;
        $this->security         = $security;
        $this->translator       = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'volunteer_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['value']) {
            $volunteer = $this->volunteerManager->findOneByNivol(
                $this->security->getPlatform(),
                $view->vars['value']
            );

            if ($volunteer) {
                $view->vars['data'] = [$volunteer->toSearchResults()];
            } else {
                $view->vars['data'] = [];
            }
        }
    }
}