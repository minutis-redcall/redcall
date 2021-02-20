<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Manager\TokenManager;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;
use Bundles\ApiBundle\Reader\CategoryCollectionReader;
use Bundles\ApiBundle\Reader\FacadeReader;
use Bundles\ApiBundle\Util;
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

    /**
     * @var FacadeReader
     */
    private $facadeReader;

    public function __construct(TokenManager $tokenManager,
        CategoryCollectionReader $categoryCollectionReader,
        FacadeReader $facadeReader)
    {
        $this->tokenManager             = $tokenManager;
        $this->categoryCollectionReader = $categoryCollectionReader;
        $this->facadeReader             = $facadeReader;
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
     * @Route(path="/documentation/{token}", name="documentation")
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
            'demo'                => $collection->getCategory(DemoController::class)->getEndpoint('hello'),
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
     * @Route(
     *     path="/console/{token}/open/{categoryName}/{endpointTitle}",
     *     name="console",
     *     defaults={"categoryName" = null, "endpointTitle" = null}
     * )
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     * @Template
     */
    public function console(Request $request, Token $token, ?string $categoryName, ?string $endpointTitle)
    {
        $endpoints = $this->extractEndpoints();

        if ($selection = $endpoint = $endpoints[$categoryName][$endpointTitle] ?? null) {
            $selection             = json_decode($selection, true);
            $selection['endpoint'] = $endpoint;
        }

        return [
            'token'         => $token,
            'form'          => $this->createConsoleForm($request, $endpoints, $selection)->createView(),
            'categoryName'  => $categoryName,
            'endpointTitle' => $endpointTitle,
        ];
    }

    /**
     * @Route(path="/console/{token}/sign", name="sign", methods={"POST"})
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     */
    public function sign(Request $request, Token $token)
    {
        $form = $this->createConsoleForm($request, $this->extractEndpoints(), null);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->json([
                'success'   => true,
                'signature' => $token->sign(
                    $form->get('method')->getData(),
                    $form->get('uri')->getData(),
                    $form->get('body')->getData() ?? ''
                ),
            ]);
        }

        return $this->json([
            'success' => false,
        ]);
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

        return $this
            ->createFormBuilder(array_merge([
                'method' => 'GET',
                'uri'    => $this->generateUrl('developer_demo_hello', ['name' => 'Bob'], UrlGeneratorInterface::ABSOLUTE_URL),
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
            ])
            ->add('run', SubmitType::class)
            ->getForm()
            ->handleRequest($request);
    }

    private function extractEndpoints() : array
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
                        'uri'    => $endpoint->getUri().$endpoint->getRequestFacade()->getFormattedExample($method, true),
                        'body'   => null,
                    ]);
                } else {
                    $endpoints[$category->getName()][$endpoint->getTitle()] = json_encode([
                        'method' => $method,
                        'uri'    => $endpoint->getUri(),
                        'body'   => $endpoint->getRequestFacade()->getFormattedExample($method, true),
                    ]);
                }
            }
        }

        return $endpoints;
    }
}