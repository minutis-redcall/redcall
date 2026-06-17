<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use App\Manager\StructureManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
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

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::OWNER, self::ACCESS])) {
            return false;
        }

        if (!$subject instanceof Campaign) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->security->isGranted('ROLE_ROOT')) {
            return true;
        }

        /** @var User $me */
        $me = $this->security->getUser();
        if (!$me || !($me instanceof UserInterface)) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($campaign->getUser()) {
            // A user has ownership of a campaign if he shares one structure with
            // the operator who triggered the campaign.
            $isOwner = $me->hasCommonStructure(
                $campaign->getUser()->getStructures()
            );

            if ($isOwner) {
                return true;
            }
        }

        // A user can access a campaign if any of the triggered volunteer has a
        // common structure with that user.
        return $me->hasCommonStructure(
            $this->structureManager->getCampaignStructures($campaign)
        );
    }
}