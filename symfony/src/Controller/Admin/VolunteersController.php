<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Volunteer;
use App\Entity\VolunteerImport;
use App\Services\VolunteerImporter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="admin/volontaires/", name="admin_volunteers_")
 */
class VolunteersController extends BaseController
{
    /**
     * @var VolunteerImporter
     */
    private $importer;

    /**
     * @param VolunteerImporter $importer
     */
    public function __construct(VolunteerImporter $importer)
    {
        $this->importer = $importer;
    }

    /**
     * @Route(name="list")
     */
    public function listAction()
    {
        $enabled = $this
            ->get('doctrine')
            ->getManager()
            ->getRepository(Volunteer::class)
            ->createQueryBuilder('v')
            ->where('v.enabled = 1');

        $disabled = $this
            ->get('doctrine')
            ->getManager()
            ->getRepository(Volunteer::class)
            ->createQueryBuilder('v')
            ->where('v.enabled = 0');

        return $this->render('admin/volunteers/list.html.twig', [
            'data' => [
                'enabled'  => [
                    'orderBy' => $this->orderBy($enabled, Volunteer::class, 'v.lastName', 'ASC', 'enabled'),
                    'rows'    => $enabled->getQuery()->getResult(),
                ],
                'disabled' => [
                    'orderBy' => $this->orderBy($disabled, Volunteer::class, 'v.lastName', 'ASC', 'disabled'),
                    'rows'    => $disabled->getQuery()->getResult(),
                ],
            ],
        ]);
    }

    /**
     * @Route(path="lock/{volunteerId}/{csrf}", name="lock")
     */
    public function lockAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        if (!$volunteer->isLocked()) {
            $volunteer->setLocked(true);
            $this->getManager()->persist($volunteer);
            $this->getManager()->flush($volunteer);
        }

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="unlock/{volunteerId}/{csrf}", name="unlock")
     */
    public function unlockAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        if ($volunteer->isLocked()) {
            $volunteer->setLocked(false);
            $this->getManager()->persist($volunteer);
            $this->getManager()->flush($volunteer);
        }

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="disable/{volunteerId}/{csrf}", name="disable")
     */
    public function disableAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        if ($volunteer->isEnabled()) {
            $volunteer->setEnabled(false);
            $this->getManager()->persist($volunteer);
            $this->getManager()->flush($volunteer);
        }

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="enable/{volunteerId}/{csrf}", name="enable")
     */
    public function enableAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        if (!$volunteer->isEnabled()) {
            $volunteer->setEnabled(true);
            $this->getManager()->persist($volunteer);
            $this->getManager()->flush($volunteer);
        }

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="run/{csrf}", name="run")
     */
    public function runAction(Request $request, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $this->importer->importOrganizationVolunteers(889);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="skills/{volunteerId}/{csrf}", name="skills")
     */
    public function skillsAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->importer->refreshVolunteerSkills($volunteer);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @param int $volunteerId
     *
     * @return Volunteer
     *
     * @throws NotFoundHttpException
     */
    private function getVolunteerOrThrowNotFound(int $volunteerId)
    {
        $volunteer = $this->getManager(Volunteer::class)->find($volunteerId);
        if (!$volunteer) {
            throw $this->createNotFoundException();
        }

        return $volunteer;
    }
}
