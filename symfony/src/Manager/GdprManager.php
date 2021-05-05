<?php

namespace App\Manager;

use App\Entity\Volunteer;

class GdprManager
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @param VolunteerManager $volunteerManager
     */
    public function __construct(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * Keep volunteer's external id & skills, anonymize everything else.
     * Volunteer gets automatically locked & disabled.
     *
     * @param Volunteer $volunteer
     */
    public function anonymize(Volunteer $volunteer)
    {
        $volunteer->setFirstName('Anonymous');
        $volunteer->setLastName('Anonymous');
        $volunteer->getPhones()->clear();
        $volunteer->setEmail(null);
        $volunteer->setLocked(true);
        $volunteer->setEnabled(false);

        $this->volunteerManager->save($volunteer);
    }
}