<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class CampaignVoter extends Voter
{
    const OWNER  = 'CAMPAIGN_OWNER';
    const ACCESS = 'CAMPAIGN_ACCESS';

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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
        if (!$user) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        if (!$campaign->getVolunteer()) {
            // Older campaigns had no "owner" access
            $attribute = self::ACCESS;
        }

        switch ($attribute) {
            case self::ACCESS:
                // A user can access a campaign if any of the triggered volunteer has a
                // common structure with that user.
                return $user->hasCommonStructure(
                    $campaign->getStructures()
                );
            case self::OWNER:
                // A user has ownership of a campaign if he shares one structure with
                // user who triggered the campaign.
                return $user->hasCommonStructure(
                    $campaign->getVolunteer()->getStructures()
                );
        }

        return false;
    }
}