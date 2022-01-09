<?php

namespace App\Form\Type;

use App\Entity\Structure;
use App\Manager\UserManager;
use App\Security\Helper\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserStructuresType extends AbstractType
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager, Security $security)
    {
        $this->userManager = $userManager;
        $this->security    = $security;
    }

    public function getParent()
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('user', $this->security->getUser());

        $resolver->setDefaults([
            'class'         => Structure::class,
            'query_builder' => function (Options $options) {
                return $this->userManager->getUserStructuresQueryBuilder($this->security->getPlatform(), $options['user']);
            },
            'choice_label'  => function (Structure $structure) {
                return $structure->getDisplayName();
            },
        ]);
    }
}