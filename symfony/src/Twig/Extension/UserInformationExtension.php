<?php

namespace App\Twig\Extension;

use App\Entity\UserInformation;
use App\Manager\UserInformationManager;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserInformationExtension extends AbstractExtension
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(UserInformationManager $userInformationManager)
    {
        $this->userInformationManager = $userInformationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('user_information', [$this, 'getUserInformation']),
        ];
    }

    /**
     * @param AbstractUser $user
     *
     * @return UserInformation
     */
    public function getUserInformation()
    {
        return $this->userInformationManager->findForCurrentUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user_information';
    }
}
