<?php

namespace App\Controller\Api\Admin;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Facade\Generic\PlatformFacade;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Facade\Http\HttpNoContentFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Every resource is attached to a platform.
 *
 * It is sometimes useful to move a resource from a platform
 * to another, for example if a user subscribes in the wrong
 * platform.
 *
 * @Route("/api/admin/platform", name="api_admin_platform_")
 * @IsGranted("ROLE_ROOT")
 */
class PlatformController
{
    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(CategoryManager $categoryManager,
        BadgeManager $badgeManager,
        UserManager $userManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager)
    {
        $this->categoryManager  = $categoryManager;
        $this->badgeManager     = $badgeManager;
        $this->userManager      = $userManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * Change a category's platform.
     *
     * @Endpoint(
     *   priority = 950,
     *   request  = @Facade(class = PlatformFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="write_category", path="/category/{externalId}", methods={"PUT"})
     * @Entity("category", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("CATEGORY", subject="category")
     */
    public function writeCategoryPlatform(Category $category, PlatformFacade $platformFacade)
    {
        $category->setPlatform($platformFacade->getPlatform());

        $this->categoryManager->save($category);

        return new HttpNoContentFacade();
    }

    /**
     * Change a badge's platform.
     *
     * @Endpoint(
     *   priority = 955,
     *   request  = @Facade(class = PlatformFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="write_badge", path="/badge/{externalId}", methods={"PUT"})
     * @Entity("badge", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("BADGE", subject="badge")
     */
    public function writeBadgePlatform(Badge $badge, PlatformFacade $platformFacade)
    {
        $badge->setPlatform($platformFacade->getPlatform());

        $this->badgeManager->save($badge);

        return new HttpNoContentFacade();
    }

    /**
     * Change a user's platform.
     *
     * @Endpoint(
     *   priority = 960,
     *   request  = @Facade(class = PlatformFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="write_user", path="/user/{email}", methods={"PUT"})
     * @Entity("user", expr="repository.findByUsernameAndCurrentPlatform(email)")
     * @IsGranted("USER", subject="user")
     */
    public function writeUserPlatform(User $user, PlatformFacade $platformFacade)
    {
        $user->setPlatform($platformFacade->getPlatform());

        $this->userManager->save($user);

        return new HttpNoContentFacade();
    }

    /**
     * Change a structure's platform.
     *
     * @Endpoint(
     *   priority = 965,
     *   request  = @Facade(class = PlatformFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="write_structure", path="/structure/{externalId}", methods={"PUT"})
     * @Entity("structure", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("STRUCTURE", subject="structure")
     */
    public function writeStructurePlatform(Structure $structure, PlatformFacade $platformFacade)
    {
        $structure->setPlatform($platformFacade->getPlatform());

        $this->structureManager->save($structure);

        return new HttpNoContentFacade();
    }

    /**
     * Change a volunteer's platform.
     *
     * @Endpoint(
     *   priority = 970,
     *   request  = @Facade(class = PlatformFacade::class),
     *   response = @Facade(class = HttpNoContentFacade::class)
     * )
     * @Route(name="write_volunteer", path="/volunteer/{externalId}", methods={"PUT"})
     * @Entity("volunteer", expr="repository.findOneByExternalIdAndCurrentPlatform(externalId)")
     * @IsGranted("VOLUNTEER", subject="volunteer")
     */
    public function writeVolunteerPlatform(Volunteer $volunteer, PlatformFacade $platformFacade)
    {
        $volunteer->setPlatform($platformFacade->getPlatform());

        $this->volunteerManager->save($volunteer);

        return new HttpNoContentFacade();
    }
}