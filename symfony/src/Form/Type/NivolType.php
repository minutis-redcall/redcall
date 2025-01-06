<?php

namespace App\Form\Type;

use App\Manager\NivolManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Callback;

class NivolType extends AbstractType
{
    private NivolManager    $nivolManager;
    private RouterInterface $router;

    public function __construct(NivolManager $nivolManager, RouterInterface $router)
    {
        $this->nivolManager = $nivolManager;
        $this->router       = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setAction(
                $this->router->generate('nivol')
            )
            ->add('nivol', TextType::class, [
                'label'       => 'Veuillez saisir votre NIVOL',
                'required'    => true,
                'constraints' => [
                    new Callback([
                        'callback' => function ($nivol, $context) {
                            if (!$this->nivolManager->getUserByNivol($nivol)) {
                                $context
                                    ->buildViolation('Le NIVOL est invalide')
                                    ->addViolation();
                            }
                        },
                    ]),
                ],
            ])
            //            ->add('_remember_me', Type\CheckboxType::class, [
            //                'label'    => 'Faire confiance à cet appareil et laisser la session ouverte',
            //                'required' => false,
            //            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Recevoir un email de connexion',
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
