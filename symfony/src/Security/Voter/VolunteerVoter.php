<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Volunteer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class VolunteerVoter extends Voter
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof Volunteer) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->security->isGranted('ROLE_ROOT')) {
            return true;
        }

        /** @var User $me */
        $me = $this->security->getUser();
        if (!$me || !($me instanceof UserInterface)) {
            return false;
        }

        /** @var Volunteer $volunteer */
        $volunteer = $subject;

        if ($me->getPlatform() !== $volunteer->getPlatform()) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (0 === $volunteer->getStructures()->count() && $me->getStructures()->count() > 0) {
            // We should not allow users without any structures to see any volunteers
            return true;
        }

        foreach ($volunteer->getStructures() as $structure) {
            if ($me->getStructures()->contains($structure)) {
                return true;
            }
        }

        return false;
    }
}
