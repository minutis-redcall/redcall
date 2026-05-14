<?php

namespace App\Controller;

use App\Entity\Expirable;
use App\Form\Type\CodeType;
use App\Form\Type\NivolType;
use App\Manager\NivolManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class NivolController extends AbstractController
{
    private NivolManager $nivolManager;

    public function __construct(NivolManager $nivolManager)
    {
        $this->nivolManager = $nivolManager;
    }

    #[Route("/nivol", name: "nivol")]
    #[Template("nivol/login.html.twig")]
    public function login(Request $request)
    {
        $nivolForm = $this
            ->createForm(NivolType::class)
            ->handleRequest($request);

        if ($nivolForm->isSubmitted() && $nivolForm->isValid()) {
            $identifier = $this->nivolManager->sendEmail($nivolForm->get('nivol')->getData());

            return $this->redirectToRoute('code', ['uuid' => $identifier]);
        }

        return [
            'nivol' => $nivolForm->createView(),
        ];
    }

    #[Route("/code/{uuid}", name: "code")]
    #[Template("nivol/code.html.twig")]
    public function code(Request $request, #[MapEntity(mapping: ['uuid' => 'uuid'])] Expirable $expirable)
    {
        $codeForm = $this
            ->createForm(CodeType::class)
            ->handleRequest($request);

        return [
            'code' => $codeForm->createView(),
        ];
    }
}