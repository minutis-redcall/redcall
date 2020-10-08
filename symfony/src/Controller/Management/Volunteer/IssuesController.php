<?php

namespace App\Controller\Management\Volunteer;

use App\Base\BaseController;
use App\Manager\VolunteerManager;
use App\Tools\PhoneNumberParser;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/issues", name="management_issues_")
 */
class IssuesController extends BaseController
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * @Route(path="/", name="index")
     */
    public function index()
    {
        return $this->render('issues/index.html.twig', [
            'issues' => $this->volunteerManager->getIssues(),
            'gaia'   => getenv('GAIA_URL'),
        ]);
    }
}