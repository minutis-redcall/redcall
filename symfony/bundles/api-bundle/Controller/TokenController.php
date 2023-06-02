<?php

namespace Bundles\ApiBundle\Controller;

use App\Component\HttpFoundation\MpdfResponse;
use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Manager\TokenManager;
use Bundles\ApiBundle\Model\Documentation\CategoryDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use Bundles\ApiBundle\Reader\CategoryCollectionReader;
use Bundles\ApiBundle\Util;
use Mpdf\Mpdf;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Route(name="developer_token_", path="/developer/token")
 * @IsGranted("ROLE_DEVELOPER")
 */
class TokenController extends AbstractController
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var CategoryCollectionReader
     */
    private $categoryCollectionReader;

    public function __construct(TokenManager $tokenManager,
        CategoryCollectionReader $categoryCollectionReader)
    {
        $this->tokenManager             = $tokenManager;
        $this->categoryCollectionReader = $categoryCollectionReader;
    }

    /**
     * @Route(path="/", name="index")
     * @Template
     */
    public function index(Request $request)
    {
        $form = $this->createTokenCreationForm($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $this->tokenManager->createTokenForUser(
                $form->get('name')->getData()
            );

            return $this->redirectToRoute('developer_token_documentation', [
                'token' => $token,
            ]);
        }

        return [
            'tokens' => $this->tokenManager->getTokensForUser(),
            'form'   => $form->createView(),
        ];
    }

    /**
     * @Route(path="/export", name="export")
     */
    public function export()
    {
        $collection = $this->categoryCollectionReader->read();

        $token = new Token();
        $token->setName('demo');
        $token->setUsername('demo');
        $token->setToken(Uuid::uuid4());
        $token->setSecret(Util::encrypt(Util::generate(Token::CLEARTEXT_SECRET_LENGTH), 'demo'));
        $token->setCreatedAt(new \DateTime());

        $context = [
            'token'               => $token,
            'category_collection' => $collection,
            'demo_get'            => $collection->getCategory(DemoController::class)->getEndpoint('helloGet'),
            'demo_post'           => $collection->getCategory(DemoController::class)->getEndpoint('helloPost'),
            'current_date'        => new \DateTime(),
        ];

        $mpdf = new Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_bottom' => 25,
        ]);

        $mpdf->SetHTMLHeader($this->renderView('@Api/export/header.html.twig', $context));
        $mpdf->SetHTMLFooter($this->renderView('@Api/export/footer.html.twig', $context));
        $mpdf->WriteHTML($this->renderView('@Api/export/body.html.twig', $context));

        return new MpdfResponse(
            $mpdf,
            sprintf('export-%s.pdf', date('Y-m-d'))
        );
    }

    /**
     * @Route(path="/remove/{csrf}/{token}", name="remove")
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     */
    public function remove(Token $token, string $csrf)
    {
        if (!$this->isCsrfTokenValid('api', $csrf)) {
            throw $this->createNotFoundException();
        }

        $this->tokenManager->remove($token);

        return $this->redirectToRoute('developer_token_index');
    }

    /**
     * @Route(path="/documentation/home/{token}", name="documentation")
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     * @Template
     */
    public function documentation(Token $token)
    {
        $collection = $this->categoryCollectionReader->read();

        return [
            'token'               => $token,
            'category_collection' => $collection,
            'demo_get'            => $collection->getCategory(DemoController::class)->getEndpoint('helloGet'),
            'demo_post'           => $collection->getCategory(DemoController::class)->getEndpoint('helloPost'),
        ];
    }

    /**
     * @Route(path="/show-secret/{token}", name="show_secret")
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     */
    public function showSecret(Token $token)
    {
        return new JsonResponse([
            'secret' => Util::decrypt($token->getSecret(), $this->getUser()->getUsername()),
        ]);
    }

    /**
     * @Route(path="/documentation/endpoint/{token}/{categoryId}/{endpointId}", name="endpoint")
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     * @Template
     */
    public function endpoint(Request $request, Token $token, string $categoryId, string $endpointId)
    {
        /** @var CategoryDescription $category */
        /** @var EndpointDescription $endpoint */
        [$category, $endpoint] = $this->extractCategoryAndEndpointFromTheirIds($categoryId, $endpointId);

        $consoleMaterial = $this->extractConsoleEndpoints();

        $consoleSelection             = json_decode($consoleMaterial[$category->getName()][$endpoint->getTitle()], true);
        $consoleSelection['endpoint'] = $consoleMaterial[$category->getName()][$endpoint->getTitle()];

        return [
            'token'    => $token,
            'console'  => $this->createConsoleForm($request, $consoleMaterial, $consoleSelection)->createView(),
            'category' => $category,
            'endpoint' => $endpoint,
        ];
    }

    /**
     * @Route(path="/console/{token}/sign", name="sign", methods={"POST"})
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     */
    public function sign(Request $request, Token $token)
    {
        $form = $this->createConsoleForm($request, $this->extractConsoleEndpoints(), null);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->json([
                'success'   => true,
                'signature' => $token->sign(
                    $form->get('method')->getData(),
                    $form->get('uri')->getData(),
                    str_replace("\r", '', $form->get('body')->getData() ?? '')
                ),
            ]);
        }

        return $this->json([
            'success'    => false,
            'violations' => $this->getErrorMessages($form),
        ]);
    }

    private function extractCategoryAndEndpointFromTheirIds(string $categoryId, string $endpointId) : array
    {
        $category   = null;
        $endpoint   = null;
        $collection = $this->categoryCollectionReader->read();
        foreach ($collection->getCategories() as $currentCategory) {
            if ($categoryId !== $currentCategory->getId()) {
                continue;
            }

            $category = $currentCategory;
            foreach ($currentCategory->getEndpoints()->getEndpoints() as $currentEndpoint) {
                if ($endpointId !== $currentEndpoint->getId()) {
                    continue;
                }

                $endpoint = $currentEndpoint;
                break 2;
            }
        }
        if (null === $category || null === $endpoint) {
            throw $this->createNotFoundException();
        }

        return [$category, $endpoint];
    }

    private function getErrorMessages(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    private function createTokenCreationForm(Request $request) : FormInterface
    {
        return $this->createFormBuilder()
                    ->add('name', TextType::class, [
                        'label'       => 'To create a new token, enter your application name:',
                        'constraints' => [
                            new NotBlank(),
                            new Length(['max' => Token::NAME_MAX_LENGTH]),
                            new Regex(['pattern' => '|^[a-zA-Z0-9 _/\-]+$|']),
                            new Callback(function ($object, ExecutionContextInterface $context, $payload) {
                                if ($this->tokenManager->findTokenByNameForUser($object)) {
                                    $context->buildViolation('A token with that name already exists.')
                                            ->atPath('name')
                                            ->addViolation();
                                }
                            }),
                        ],
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'Create new token',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }

    private function createConsoleForm(Request $request, array $endpoints, ?array $selection) : FormInterface
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];

        $builder = $this
            ->createFormBuilder(array_merge([
                'method' => 'GET',
                'uri'    => $this->generateUrl('developer_demo_hello_get', ['name' => 'Bob'], UrlGeneratorInterface::ABSOLUTE_URL),
            ], $selection ?? []), [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('endpoint', ChoiceType::class, [
                'label'   => false,
                'choices' => $endpoints,
            ])
            ->add('method', ChoiceType::class, [
                'label'       => false,
                'choices'     => array_combine($methods, $methods),
                'constraints' => [
                    new NotBlank(),
                    new Choice(['choices' => $methods]),
                ],
            ])
            ->add('uri', UrlType::class, [
                'label'       => false,
                'constraints' => [
                    new NotBlank(),
                    new Url(),
                    new Callback(function ($object, ExecutionContextInterface $context, $payload) {
                        $base = sprintf('%s/', getenv('WEBSITE_URL'));
                        if (0 !== strpos($object, $base)) {
                            $context->addViolation(sprintf('Base URI should stay "%s".', $base));
                        }
                    }),
                ],
            ])
            ->add('body', TextareaType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'rows'     => 6,
                    'readonly' => true,
                ],
                'trim'     => false,
            ])
            ->add('run', SubmitType::class);

        // Disable choice mapping
        $builder->get('endpoint')->resetViewTransformers();

        return $builder
            ->getForm()
            ->handleRequest($request);
    }

    private function extractConsoleEndpoints() : array
    {
        $categoryCollection = $this->categoryCollectionReader->read();
        $endpoints          = [];
        foreach ($categoryCollection->getCategories() as $category) {
            foreach ($category->getEndpoints()->getEndpoints() as $endpoint) {
                /** @var EndpointDescription $endpoint */
                $method = $endpoint->getMethods()[0];

                if ('GET' === $method) {
                    $endpoints[$category->getName()][$endpoint->getTitle()] = json_encode([
                        'method' => $method,
                        'uri'    => $endpoint->getUri().($endpoint->getRequestFacade() ? $endpoint->getRequestFacade()->getFormattedExample($method, true) : null),
                        'body'   => null,
                    ]);
                } else {
                    $endpoints[$category->getName()][$endpoint->getTitle()] = json_encode([
                        'method' => $method,
                        'uri'    => $endpoint->getUri(),
                        'body'   => $endpoint->getRequestFacade() ? $endpoint->getRequestFacade()->getFormattedExample($method, true) : null,
                    ]);
                }
            }
        }

        return $endpoints;
    }
}