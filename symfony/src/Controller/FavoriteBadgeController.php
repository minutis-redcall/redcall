<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\Badge;
use App\Entity\User;
use App\Form\Type\BadgeWidgetType;
use App\Manager\BadgeManager;
use App\Manager\UserManager;
use App\Model\Csrf;
use App\Security\Helper\Security;
use Bundles\SettingsBundle\Manager\SettingManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="favorite-badge", name="favorite_badge_")
 */
class FavoriteBadgeController extends BaseController
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager,
        BadgeManager $badgeManager,
        SettingManager $settingManager,
        Security $security)
    {
        $this->userManager    = $userManager;
        $this->badgeManager   = $badgeManager;
        $this->settingManager = $settingManager;
        $this->security       = $security;
    }

    /**
     * @Route(name="index")
     * @Template()
     */
    public function index(Request $request)
    {
        // Initializing with the currently public badges
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$this->settingManager->get($key = sprintf('fav_badge_%d', $user->getId()))) {
            foreach ($this->badgeManager->getPublicBadges($this->security->getPlatform()) as $badge) {
                $user->addFavoriteBadge($badge);
            }
            $this->userManager->save($user);
            $this->settingManager->set($key, 'ok');
        }

        $form = $this->createFormBuilder()
                     ->add('badge', BadgeWidgetType::class, [
                         'label'    => 'favorite_badge.form.badge',
                         'multiple' => true,
                     ])
                     ->add('submit', SubmitType::class, [
                         'label' => 'favorite_badge.form.submit',
                     ])
                     ->getForm()
                     ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            foreach ($form->get('badge')->getData() as $badge) {
                $user->addFavoriteBadge($badge);
            }
            $this->userManager->save($user);

            return $this->redirectToRoute('favorite_badge_index');
        }

        return [
            'form'          => $form->createView(),
            'public_badges' => $this->badgeManager->getPublicBadges($this->security->getPlatform()),
        ];
    }

    /**
     * @Route(path="/delete/{csrf}/{id}", name="delete")
     */
    public function delete(Badge $badge, Csrf $csrf)
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->removeFavoriteBadge($badge);

        $this->userManager->save($user);

        return $this->redirectToRoute('favorite_badge_index');
    }
}