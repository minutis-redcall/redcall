<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\UserInformation;
use App\Manager\UserInformationManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class CampaignVoter extends Voter
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @param Security               $security
     * @param UserInformationManager $userInformationManager
     */
    public function __construct(Security $security, UserInformationManager $userInformationManager)
    {
        $this->security               = $security;
        $this->userInformationManager = $userInformationManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof Campaign) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var UserInformation $userInformation */
        $userInformation = $this->userInformationManager->findOneByUser($token->getUser());
        if (!$userInformation) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        return $userInformation->getStructures()->contains($campaign->getStructure());
    }
}