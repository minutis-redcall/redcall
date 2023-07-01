<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Component\HttpFoundation\NoContentResponse;
use App\Entity\Badge;
use App\Entity\Category;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Manager\PlatformConfigManager;
use App\Model\Csrf;
use App\Model\PlatformConfig;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/categories", name="admin_category_")
 */
class CategoryController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(PaginationManager $paginationManager,
        CategoryManager $categoryManager,
        BadgeManager $badgeManager,
        PlatformConfigManager $platformManager,
        TranslatorInterface $translator)
    {
        $this->paginationManager = $paginationManager;
        $this->categoryManager   = $categoryManager;
        $this->badgeManager      = $badgeManager;
        $this->platformManager   = $platformManager;
        $this->translator        = $translator;
    }

    /**
     * @Route(name="index", path="/")
     * @Template("admin/category/categories.html.twig")
     */
    public function listCategories(Request $request) : array
    {
        $searchForm = $this->createSearchForm($request, 'admin.category.search');

        $categories = $this->paginationManager->getPager(
            $this->categoryManager->getSearchInCategoriesQueryBuilder(
                $this->getPlatform(),
                $searchForm->get('criteria')->getData()
            )
        );

        return [
            'categories' => $categories,
            'search'     => $searchForm->createView(),
            'platforms'  => $this->getPlatforms(),
        ];
    }

    /**
     * @Route(name="form", path="/form-for-{id}", defaults={"id" = null})
     */
    public function categoryForm(Request $request, Category $category = null) : Response
    {
        if ($category && !$this->isGranted('CATEGORY', $category)) {
            throw $this->createAccessDeniedException();
        }

        if (!$category) {
            $category = new Category();
            $category->setPlatform($this->getPlatform());
            $category->setExternalId(Uuid::uuid4());
        }

        $form = $this->createFormBuilder($category)
                     ->add('name', TextType::class, [
                         'label' => 'admin.category.form.name',
                     ])
                     ->add('priority', NumberType::class, [
                         'label' => 'admin.category.form.priority',
                     ])
                     ->add('submit', SubmitType::class, [
                         'attr' => [
                             'class' => 'd-none',
                         ],
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryManager->save($category);

            return $this->json([
                'saved' => true,
                'id'    => $category->getId(),
                'view'  => $this->renderView('admin/category/category.html.twig', [
                    'category'  => $category,
                    'platforms' => $this->getPlatforms(),
                ]),
            ]);
        }

        return $this->json([
            'saved' => false,
            'title' => $category->getId() ? $this->translator->trans('admin.category.edit') : $this->translator->trans('admin.category.create'),
            'body'  => $this->renderView('widget/form.html.twig', [
                'form' => $form->createView(),
            ]),
        ]);
    }

    /**
     * @Route(name="delete", path="/delete-category-{id}/{token}"))
     * @IsGranted("CATEGORY", subject="category")
     */
    public function deleteCategory(Category $category, Csrf $token)
    {
        if ($category->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $this->categoryManager->remove($category);

        return new NoContentResponse();
    }

    /**
     * @Route(name="toggle_lock", path="/lock-unlock-{id}/{token}"))
     * @IsGranted("CATEGORY", subject="category")
     */
    public function toggleLockCategory(Category $category, Csrf $token)
    {
        if (!$category->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $category->setLocked(1 - $category->isLocked());

        $this->categoryManager->save($category);

        return $this->json([
            'view' => $this->renderView('admin/category/category.html.twig', [
                'category'  => $category,
                'platforms' => $this->getPlatforms(),
            ]),
        ]);
    }

    /**
     * @Route(name="toggle_enable", path="/enable-disable-{id}/{token}"))
     * @IsGranted("CATEGORY", subject="category")
     */
    public function toggleEnableCategory(Category $category, Csrf $token)
    {
        $category->setEnabled(1 - $category->isEnabled());

        $this->categoryManager->save($category);

        return $this->json([
            'view' => $this->renderView('admin/category/category.html.twig', [
                'category'  => $category,
                'platforms' => $this->getPlatforms(),
            ]),
        ]);
    }

    /**
     * @Route(name="badges", path="/list-badges-in-category-{id}")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function listBadgeInCategory(Category $category)
    {
        return $this->json([
            'title' => $this->translator->trans('admin.category.badges_list', [
                '%name%' => htmlentities($category->getName()),
            ]),
            'body'  => $this->renderView('admin/category/badges_in_category.html.twig', [
                'category' => $category,
            ]),
        ]);
    }

    /**
     * @Route(name="add_badge", path="/add-badge-in-category-{id}/{token}"))
     * @IsGranted("CATEGORY", subject="category")
     */
    public function addBadgeInCategory(Request $request, Category $category, Csrf $token)
    {
        if (!$badge = $this->badgeManager->find($request->get('badge'))) {
            throw $this->createNotFoundException();
        }

        $category->addBadge($badge);

        $this->badgeManager->save($badge);

        return $this->json([
            'body' => $this->renderView('admin/category/badges_in_category.html.twig', [
                'category' => $category,
            ]),
        ]);
    }

    /**
     * @Route(name="refresh", path="/refresh-category-category-{id}")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function refreshCategoryCard(Category $category)
    {
        return $this->render('admin/category/category.html.twig', [
            'category'  => $category,
            'platforms' => $this->getPlatforms(),
        ]);
    }

    /**
     * @Route(name="delete_badge", path="/delete-badge-{badgeId}-in-category-{categoryId}/{token}"))
     * @Entity("category", expr="repository.find(categoryId)")
     * @Entity("badge", expr="repository.find(badgeId)")
     * @IsGranted("CATEGORY", subject="category")
     * @IsGranted("BADGE", subject="badge")
     */
    public function deleteBadgeInCategory(Category $category, Badge $badge, Csrf $token)
    {
        $category->removeBadge($badge);

        $this->badgeManager->save($badge);

        return new NoContentResponse();
    }

    /**
     * @Route(name="update_platform", path="/change-platform/{csrf}/{id}/{platform}")
     * @IsGranted("ROLE_ROOT")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function changePlatform(Category $category, Csrf $csrf, PlatformConfig $platform)
    {
        $category->setPlatform($platform);

        $this->categoryManager->save($category);

        return $this->redirectToRoute('admin_category_refresh', [
            'id' => $category->getId(),
        ]);
    }

    protected function getPlatforms() : ?array
    {
        if (!$this->getUser()->isRoot()) {
            return null;
        }

        return $this->platformManager->getAvailablePlatforms();
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