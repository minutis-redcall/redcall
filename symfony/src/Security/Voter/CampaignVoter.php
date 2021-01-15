<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use App\Manager\StructureManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CampaignVoter extends Voter
{
    const OWNER  = 'CAMPAIGN_OWNER';
    const ACCESS = 'CAMPAIGN_ACCESS';

    /**
     * @var Security
     */
    private $security;

    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(Security $security, StructureManager $structureManager)
    {
        $this->security         = $security;
        $this->structureManager = $structureManager;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::OWNER, self::ACCESS])) {
            return false;
        }

        if (!$subject instanceof Campaign) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user || !($user instanceof UserInterface)) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        if ($campaign->getVolunteer() && $campaign->getVolunteer()->getUser()) {
            // A user has ownership of a campaign if he shares one structure with
            // user who triggered the campaign.
            $isOwner = $user->hasCommonStructure(
                $campaign->getVolunteer()->getUser()->getStructures()
            );

            if ($isOwner) {
                return true;
            }
        }

        // A user can access a campaign if any of the triggered volunteer has a
        // common structure with that user.
        return $user->hasCommonStructure(
            $this->structureManager->getCampaignStructures($campaign)
        );
    }
}