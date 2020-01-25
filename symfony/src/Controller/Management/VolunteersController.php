<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Entity\Volunteer;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="management/volunteers", name="management_volunteers_")
 */
class VolunteersController extends BaseController
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param UserInformationManager $userInformationManager
     * @param VolunteerManager       $volunteerManager
     * @param PegassManager          $pegassManager
     * @param PaginationManager      $paginationManager
     * @param KernelInterface        $kernel
     */
    public function __construct(UserInformationManager $userInformationManager,
        VolunteerManager $volunteerManager,
        PegassManager $pegassManager,
        PaginationManager $paginationManager,
        KernelInterface $kernel)
    {
        $this->userInformationManager = $userInformationManager;
        $this->volunteerManager       = $volunteerManager;
        $this->pegassManager          = $pegassManager;
        $this->paginationManager      = $paginationManager;
        $this->kernel                 = $kernel;
    }

    /**
     * @Route(name="list")
     */
    public function listAction(Request $request)
    {
        $search = $this->createSearchForm($request);

        $criteria = null;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $this->volunteerManager->searchAllQueryBuilder($criteria);
        } else {
            $queryBuilder = $this->volunteerManager->searchForCurrentUser($criteria);
        }

        return $this->render('management/volunteers/list.html.twig', [
            'search'     => $search->createView(),
            'volunteers' => $this->paginationManager->getPager($queryBuilder),
        ]);
    }

    /**
     * @Route(name="force_update", path="/force-update/{csrf}/{id}")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function forceUpdate(Request $request, Volunteer $volunteer, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('volunteers', $csrf);

        if (!$volunteer->canForcePegassUpdate()) {
            return $this->redirectToRoute('management_volunteers_list', $request->query->all());
        }

        // Just in case Pegass database would contain some RCE?
        if (!preg_match('/^[a-zA-Z0-9]+$/', $volunteer->getIdentifier())) {
            return $this->redirectToRoute('management_volunteers_list', $request->query->all());
        }

        // Prevents multiple clicks
        $volunteer->setLastPegassUpdate(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->structureManager->save($volunteer);

        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s pegass --volunteer %d', escapeshellarg($console), $volunteer->getIdentifier());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));

        return $this->redirectToRoute('management_volunteers_list', $request->query->all());
    }

    /**
     * @Route(name="pegass", path="/pegass/{id}")
     * @IsGranted("ROLE_ADMIN")
     */
    public function pegass(Volunteer $volunteer)
    {
        $nivol = str_pad($volunteer->getNivol(), 12, '0', STR_PAD_LEFT);

        $entity = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $nivol, false);
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $this->render('management/volunteers/pegass.html.twig', [
            'volunteer' => $volunteer,
            'pegass'    => json_encode($entity->getContent(), JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return FormInterface
     */
    private function createSearchForm(Request $request): FormInterface
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => 'manage_volunteers.search.label',
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'manage_volunteers.search.button',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}
