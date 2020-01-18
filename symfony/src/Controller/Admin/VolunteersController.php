<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Entity\Volunteer;
use App\Form\Type\VolunteerType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="admin/volontaires/", name="admin_volunteers_")
 */
class VolunteersController extends BaseController
{
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

        $this->getManager(Volunteer::class)->lock($volunteer);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="unlock/{volunteerId}/{csrf}", name="unlock")
     */
    public function unlockAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->getManager(Volunteer::class)->unlock($volunteer);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="disable/{volunteerId}/{csrf}", name="disable")
     */
    public function disableAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->getManager(Volunteer::class)->disable($volunteer);
        $this->getManager(Volunteer::class)->lock($volunteer);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="enable/{volunteerId}/{csrf}", name="enable")
     */
    public function enableAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->getManager(Volunteer::class)->enable($volunteer);
        $this->getManager(Volunteer::class)->lock($volunteer);

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
     * @Route(path="refresh-general/{volunteerId}/{csrf}", name="refresh_general")
     */
    public function refreshGeneralAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->importer->refreshVolunteerGeneral($volunteer);

        $this->success('manage_volunteers.action.refresh_success');

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="refresh-skills/{volunteerId}/{csrf}", name="refresh_skills")
     */
    public function refreshSkillsAction(Request $request, int $volunteerId, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('manage_volunteers', $csrf);

        $volunteer = $this->getVolunteerOrThrowNotFound($volunteerId);

        $this->importer->refreshVolunteerSkills($volunteer);

        return $this->redirectToRoute('admin_volunteers_list', $request->query->all());
    }

    /**
     * @Route(path="manual-update/{id}", name="manual_update")
     */
    public function manualUpdateAction(Request $request, Volunteer $entity)
    {
        $isCreate = !$entity->getId();

        $form = $this
            ->createForm(VolunteerType::class, $entity)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Locks volunteer from being removed at next Pegass sync
            $entity->setLocked(true);

            $this->getManager(Volunteer::class)->save($entity);

            if ($isCreate) {
                $this->success('manage_volunteers.form.added');
            } else {
                $this->success('manage_volunteers.form.updated');
            }

            return $this->redirectToRoute('admin_volunteers_list');
        }

        return $this->render('admin/volunteers/form.html.twig', [
            'form'     => $form->createView(),
            'isCreate' => $isCreate,
        ]);
    }

    /**
     * @Route(path="/create", name="create")
     */
    public function createAction(Request $request)
    {
        return $this->manualUpdateAction($request, new Volunteer());
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
