<?php

namespace App\Controller;

use App\Form\Type\CodeType;
use App\Form\Type\NivolType;
use App\Manager\NivolManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NivolController extends AbstractController
{
    private NivolManager $nivolManager;

    public function __construct(NivolManager $nivolManager)
    {
        $this->nivolManager = $nivolManager;
    }

    /**
     * @Route("/nivol", name="nivol")
     * @Template()
     */
    public function login(Request $request)
    {
        $nivolForm = $this
            ->createForm(NivolType::class)
            ->handleRequest($request);

        if ($nivolForm->isSubmitted() && $nivolForm->isValid()) {
            $identifier = $this->nivolManager->sendEmail($nivolForm->get('nivol')->getData());

            return $this->redirectToRoute('code', ['identifier' => $identifier]);
        }

        return [
            'nivol' => $nivolForm->createView(),
        ];
    }

    /**
     * @Route("/code/{identifier}", name="code")
     * @Entity("expirable", expr="repository.findOneByUuid(identifier)")
     * @Template()
     */
    public function code(Request $request)
    {
        $codeForm = $this
            ->createForm(CodeType::class)
            ->handleRequest($request);

        return [
            'code' => $codeForm->createView(),
        ];
    }
}