<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Entity\Structure;
use App\Form\Type\StructureImportType;
use App\Manager\StructureManager;
use App\Structure\StructureImporter;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use DateTime;
use DateTimeZone;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @Route(path="management/structures", name="management_structures_")
 */
class StructuresController extends BaseController
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var StructureImporter
     */
    private $structureImporter;

    /**
     * @param StructureManager  $structureManager
     * @param PaginationManager $paginationManager
     * @param PegassManager     $pegassManager
     * @param KernelInterface   $kernel
     * @param StructureImporter $structureImporter
     */
    public function __construct(StructureManager $structureManager,
        PaginationManager $paginationManager,
        PegassManager $pegassManager,
        KernelInterface $kernel,
        StructureImporter $structureImporter
    )
    {
        $this->structureManager  = $structureManager;
        $this->paginationManager = $paginationManager;
        $this->pegassManager     = $pegassManager;
        $this->kernel            = $kernel;
        $this->structureImporter = $structureImporter;
    }

    /**
     * @Route(name="list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\DBALException
     */
    public function listAction(Request $request)
    {
        // CSV import form.
        $violationList = new ConstraintViolationList();
        $importForm = $this->createForm(StructureImportType::class);
        $importForm->handleRequest($request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            $file = $importForm->get('file')->getData();
            $this->structureImporter->getDataProvider()->init([
                'file' => $file,
                'delimiter' => ';',
            ]);
            $violationList = $this->structureImporter->import();
        }

        // Search form.
        $search = $this->createSearchForm($request);

        $criteria = null;
        if ($search->isSubmitted() && $search->isValid()) {
            $criteria = $search->get('criteria')->getData();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $this->structureManager->searchAllQueryBuilder($criteria);
        } else {
            $queryBuilder = $this->structureManager->searchForCurrentUserQueryBuilder($criteria);
        }

        $importForm = $this->createForm(StructureImportType::class);

        return $this->render('management/structures/list.html.twig', [
            'search'     => $search->createView(),
            'structures' => $this->paginationManager->getPager($queryBuilder),
            'importForm' => $importForm->createView(),
            'importViolationList' => $violationList,
        ]);
    }

    /**
     * @Route(name="force_update", path="/force-update/{csrf}/{id}")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function forceUpdate(Request $request, Structure $structure, string $csrf)
    {
        $this->validateCsrfOrThrowNotFoundException('structures', $csrf);

        if (0 === $structure->getIdentifier()) {
            return $this->redirectToRoute('management_structures_list', $request->query->all());
        }

        if (!$structure->canForcePegassUpdate()) {
            return $this->redirectToRoute('management_structures_list', $request->query->all());
        }

        // Just in case Pegass database would contain some RCE?
        if (!preg_match('/^[a-zA-Z0-9]+$/', $structure->getIdentifier())) {
            return $this->redirectToRoute('management_structures_list', $request->query->all());
        }

        // Prevents multiple clicks
        $structure->setLastPegassUpdate(new DateTime('now', new DateTimeZone('UTC')));
        $this->structureManager->save($structure);

        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s pegass --structure %s', escapeshellarg($console), $structure->getIdentifier());
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));

        return $this->redirectToRoute('management_structures_list', $request->query->all());
    }

    /**
     * @Route(name="pegass", path="/pegass/{id}")
     * @IsGranted("ROLE_ADMIN")
     */
    public function pegass(Structure $structure, Request $request)
    {
        if (0 === $structure->getIdentifier()) {
            return $this->redirectToRoute('management_structures_list', $request->query->all());
        }

        $entity = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, $structure->getIdentifier(), false);
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        return $this->render('management/structures/pegass.html.twig', [
            'structure' => $structure,
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
                        'label'    => 'manage_structures.search.label',
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'manage_structures.search.button',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}
