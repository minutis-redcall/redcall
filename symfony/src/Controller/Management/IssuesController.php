<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Manager\VolunteerManager;
use App\Tools\PhoneNumberParser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route(path="management/issues", name="management_issues_")
 */
class IssuesController extends BaseController
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param VolunteerManager   $volunteerManager
     * @param ValidatorInterface $validator
     */
    public function __construct(VolunteerManager $volunteerManager, ValidatorInterface $validator)
    {
        $this->volunteerManager = $volunteerManager;
        $this->validator        = $validator;
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