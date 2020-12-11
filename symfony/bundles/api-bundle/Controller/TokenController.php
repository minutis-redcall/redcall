<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Manager\TokenManager;
use Bundles\ApiBundle\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Route(name="developer_token_", path="/developer/token")
 * @IsGranted("ROLE_DEVELOPER")
 */
class TokenController extends BaseController
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * @Route(path="/", name="index")
     */
    public function index(Request $request)
    {
        $form = $this->createTokenCreationForm($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tokenManager->createTokenForUser(
                $form->get('name')->getData()
            );

            return $this->redirectToRoute('developer_token_index');
        }

        return $this->render('@Api/token/index.html.twig', [
            'pager' => $this->getPager(
                $this->tokenManager->getTokensQueryBuilderForUser()
            ),
            'form'  => $form->createView(),
        ]);
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
     * @Route(path="/details/{token}", name="details")
     * @Entity("token", expr="repository.findOneByToken(token)")
     * @IsGranted("TOKEN", subject="token")
     */
    public function details(Token $token)
    {
        return $this->render('@Api/token/details.html.twig', [
            'token' => $token,
        ]);
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

    private function createTokenCreationForm(Request $request) : FormInterface
    {
        return $this->createFormBuilder()
                    ->add('name', TextType::class, [
                        'label'       => 'To create a new token, enter your application name:',
                        'constraints' => [
                            new NotBlank(),
                            new Length(['max' => Token::NAME_MAX_LENGTH]),
                            new Regex(['pattern' => '|[a-zA-Z0-9 _/\-]+|']),
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
}