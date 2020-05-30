<?php

namespace App\Manager;

use App\Entity\Volunteer;
use App\Entity\VolunteerSession;
use App\Repository\VolunteerSessionRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VolunteerSessionManager
{
    /**
     * @var VolunteerSessionRepository
     */
    private $volunteerSessionRepository;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param VolunteerSessionRepository $volunteerSessionRepository
     * @param SessionInterface           $session
     */
    public function __construct(VolunteerSessionRepository $volunteerSessionRepository, SessionInterface $session)
    {
        $this->volunteerSessionRepository = $volunteerSessionRepository;
        $this->session = $session;
    }

    /**
     * @param Volunteer $volunteer
     */
    public function createSession(Volunteer $volunteer) : string
    {
        if ($this->session->get('volunteer-session')) {
            return $this->session->get('volunteer-session');
        }

        $session = new VolunteerSession();
        $session->setVolunteer($volunteer);
        $session->setSessionId(Uuid::uuid4());
        $session->setCreatedAt(new \DateTime());

        $this->session->set('volunteer-session', $session->getSessionId());

        $this->volunteerSessionRepository->save($session);

        return $session->getSessionId();
    }

    public function removeSession(VolunteerSession $session)
    {
        $this->session->remove('volunteer-session');

        $this->volunteerSessionRepository->remove($session);
    }

    public function clearExpired()
    {
        $this->volunteerSessionRepository->clearExpired(86400);
    }
}