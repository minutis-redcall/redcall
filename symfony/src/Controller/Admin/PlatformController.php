<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Manager\PlatformConfigManager;
use App\Manager\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * @IsGranted("ROLE_ROOT")
 * @Route(path="admin/platform", name="admin_platform_")
 */
class PlatformController extends AbstractController
{
    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(PlatformConfigManager $platformManager, UserManager $userManager)
    {
        $this->platformManager = $platformManager;
        $this->userManager     = $userManager;
    }

    /**
     * @Template
     */
    public function renderSwitch(Request $request)
    {
        $form = $this->createSwitchForm($request);

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route(name="switch_me", path="/switch-me")
     */
    public function switchMe(Request $request)
    {
        $form = $this->createSwitchForm($request);

        if (!($form->isSubmitted() && $form->isValid())) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $user->setPlatform($form->get('platform')->getData());

        $this->userManager->save($user);

        return $this->redirectToRoute('home');
    }

    private function createSwitchForm(Request $request) : FormInterface
    {
        $platforms = $this->platformManager->getAvailablePlatforms();

        /** @var User $user */
        $user = $this->getUser();

        $choices = [];
        foreach ($platforms as $platform) {
            $choices[ucfirst($platform->getLabel())] = $platform->getName();
        }

        return $this
            ->createFormBuilder([
                'platform' => $user->getPlatform(),
            ])
            ->setAction($this->generateUrl('admin_platform_switch_me'))
            ->add('platform', ChoiceType::class, [
                'label'       => false,
                'choices'     => $choices,
                'constraints' => [
                    new Choice(['choices' => $choices]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.ok',
            ])
            ->getForm()
            ->handleRequest($request);
    }
}