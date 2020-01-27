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
     * Keep volunteer's nivol & skills, anonymize everything else.
     * Volunteer gets automatically locked & disabled.
     *
     * @param Volunteer $volunteer
     */
    public function anonymize(Volunteer $volunteer)
    {
        $volunteer->setFirstName('Anonymous');
        $volunteer->setLastName('Anonymous');
        $volunteer->setPhoneNumber(null);
        $volunteer->setEmail(null);
        $volunteer->setLocked(true);
        $volunteer->setEnabled(false);

        $this->volunteerManager->save($volunteer);
    }
}