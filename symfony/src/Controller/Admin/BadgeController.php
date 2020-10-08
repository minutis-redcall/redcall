<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\BadgeManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/badges", name="admin_badge_")
 */
class BadgeController extends BaseController
{
    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @param BadgeManager $badgeManager
     */
    public function __construct(BadgeManager $badgeManager)
    {
        $this->badgeManager = $badgeManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/badge/badges.html.twig")
     */
    public function index(Request $request) : array
    {
        $searchForm = $this->createSearchForm($request, 'admin.badge.search');

        $badges = $this->getPager(
            $this->badgeManager->getSearchInPublicBadgesQueryBuilder(
                $searchForm->get('criteria')->getData()
            )
        );

        return [
            'badges' => $badges,
            'search' => $searchForm->createView(),
        ];
    }

    private function createSearchForm(Request $request, string $label) : FormInterface
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => $label,
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'base.button.search',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }
}