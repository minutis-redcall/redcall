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
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user || !($user instanceof UserInterface)) {
            return false;
        }

        /** @var Volunteer $volunteer */
        $volunteer = $subject;

        if (0 === $volunteer->getStructures()->count()) {
            return true;
        }

        foreach ($volunteer->getStructures() as $structure) {
            if ($user->getStructures()->contains($structure)) {
                return true;
            }
        }

        return false;
    }
}
