<?php

namespace App\Security\Voter;

use App\Entity\Communication;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Bundle\SecurityBundle\Security;

class CommunicationVoter extends Voter
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!($subject instanceof Communication)) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Communication $communication */
        $communication = $subject;

        return $this->security->isGranted('CAMPAIGN_ACCESS', $communication->getCampaign());
    }
}