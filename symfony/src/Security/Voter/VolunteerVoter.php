<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Volunteer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
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
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Volunteer) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
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

        if ($this->security->isGranted('ROLE_ADMIN')) {
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
