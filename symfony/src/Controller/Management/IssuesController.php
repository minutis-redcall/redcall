<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Tools\PhoneNumberParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $this->validator = $validator;
    }

    /**
     * @Route(path="/", name="index")
     */
    public function index()
    {
        return $this->render('issues/index.html.twig', [
            'issues' => $this->volunteerManager->getIssues(),
            'gaia' => getenv('GAIA_URL'),
        ]);
    }

    /**
     * @Route(path="/save/{csrf}/{nivol}", name="save")
     */
    public function save(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('issues', $csrf);

        $volunteer->setPhoneNumber($request->get('phone'));
        $volunteer->setEmail($request->get('email'));

        $errors = $this->validator->validate($volunteer);

        if (0 === count($errors)) {
            if ($volunteer->getPhoneNumber()) {
                $volunteer->setPhoneNumber(PhoneNumberParser::parse($volunteer->getPhoneNumber()));
            }

            $volunteer->setLocked(true);

            $this->volunteerManager->save($volunteer);

            return new JsonResponse([
                'success' => true,
            ]);
        }

        return new JsonResponse([
            'success' => false,
        ]);
    }
}