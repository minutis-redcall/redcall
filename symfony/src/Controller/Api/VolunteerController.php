<?php

namespace App\Controller\Api;

use App\Manager\VolunteerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * A volunteer is a physical person belonging to the Red Cross.
 *
 * @Route("/api/volunteer", name="api_volunteer_")
 */
class VolunteerController extends AbstractController
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @Route(name="records", methods={"GET"})
     */
    public function records() : array
    {
        return [];
    }

}