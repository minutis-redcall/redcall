<?php

namespace App\Manager;

use App\Entity\Volunteer;
use App\Entity\VolunteerSession;
use App\Repository\VolunteerSessionRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

class VolunteerSessionManager
{
    /**
     * @var VolunteerSessionRepository
     */
    private $volunteerSessionRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(VolunteerSessionRepository $volunteerSessionRepository, RequestStack $requestStack)
    {
        $this->volunteerSessionRepository = $volunteerSessionRepository;
        $this->requestStack               = $requestStack;
    }

    /**
     * @param Volunteer $volunteer
     */
    public function createSession(Volunteer $volunteer) : string
    {
        $session = $this->requestStack->getSession();

        if ($session->get('volunteer-session')) {
            return $session->get('volunteer-session');
        }

        $vSession = new VolunteerSession();
        $vSession->setVolunteer($volunteer);
        $vSession->setSessionId(Uuid::uuid4());
        $vSession->setCreatedAt(new \DateTime());

        $session->set('volunteer-session', $vSession->getSessionId());

        $this->volunteerSessionRepository->save($vSession);

        return $vSession->getSessionId();
    }

    public function removeSession(VolunteerSession $session)
    {
        $this->requestStack->getSession()->remove('volunteer-session');

        $this->volunteerSessionRepository->remove($session);
    }

    public function clearExpired()
    {
        $this->volunteerSessionRepository->clearExpired(86400);
    }
}