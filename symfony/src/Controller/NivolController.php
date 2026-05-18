<?php

namespace App\Controller;

use App\Entity\Expirable;
use App\Form\Type\CodeType;
use App\Form\Type\NivolType;
use App\Manager\NivolManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class NivolController extends AbstractController
{
    private NivolManager    $nivolManager;
    private LoggerInterface $logger;

    public function __construct(NivolManager $nivolManager, ?LoggerInterface $logger = null)
    {
        $this->nivolManager = $nivolManager;
        $this->logger       = $logger ?? new NullLogger();
    }

    #[Route("/nivol", name: "nivol")]
    #[Template("nivol/login.html.twig")]
    public function login(Request $request)
    {
        $this->logger->info('NivolController::login entered', [
            'method' => $request->getMethod(),
            'ip'     => $request->getClientIp(),
        ]);

        $nivolForm = $this
            ->createForm(NivolType::class)
            ->handleRequest($request);

        if ($nivolForm->isSubmitted() && !$nivolForm->isValid()) {
            $errors = [];
            foreach ($nivolForm->getErrors(true) as $error) {
                $origin   = $error->getOrigin();
                $errors[] = [
                    'field'   => $origin ? $origin->getName() : '(form)',
                    'message' => $error->getMessage(),
                ];
            }

            $this->logger->warning('NivolController::login: form invalid', [
                'errors' => $errors,
            ]);
        }

        if ($nivolForm->isSubmitted() && $nivolForm->isValid()) {
            $nivolValue = $nivolForm->get('nivol')->getData();

            $this->logger->info('NivolController::login: nivol submitted, sending email', [
                'nivol_length' => is_string($nivolValue) ? strlen($nivolValue) : 0,
            ]);

            $identifier = $this->nivolManager->sendEmail($nivolValue);

            if (null === $identifier) {
                $this->logger->warning('NivolController::login: sendEmail returned null (no user matched nivol)');
            }

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