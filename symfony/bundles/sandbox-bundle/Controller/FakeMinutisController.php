<?php

namespace Bundles\SandboxBundle\Controller;

use App\Model\Csrf;
use Bundles\SandboxBundle\Base\BaseController;
use Bundles\SandboxBundle\Manager\FakeOperationManager;
use Bundles\SandboxBundle\Manager\FakeOperationResourceManager;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/fake-minutis", name="fake_minutis_")
 */
class FakeMinutisController extends BaseController
{
    /**
     * @var FakeOperationManager
     */
    private $operationManager;

    /**
     * @var FakeOperationResourceManager
     */
    private $operationResourceManager;

    public function __construct(FakeOperationManager $operationManager,
        FakeOperationResourceManager $operationResourceManager)
    {
        $this->operationManager         = $operationManager;
        $this->operationResourceManager = $operationResourceManager;
    }

    /**
     * @Route("/", name="list")
     * @Template()
     */
    public function listAction()
    {
        return [
            'operations' => $this->operationManager->all(),
        ];
    }

    /**
     * @Route("/clear/{token}", name="clear")
     */
    public function clear(Csrf $token)
    {
        $this->operationResourceManager->clear();
        $this->operationManager->clear();

        return $this->redirectToRoute('fake_minutis_list');
    }
}