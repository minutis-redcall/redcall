<?php

namespace App\Tests\Sync\Reconciliation;

use App\Command\AnnuaireNationalCommand;
use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Sync\Reconciliation\RtmrReconciliator;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RtmrReconciliatorTest extends KernelTestCase
{
    private RtmrReconciliator $reconciliator;
    private UserManager $userManager;
    private VolunteerManager $volunteerManager;
    private BadgeManager $badgeManager;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->reconciliator    = self::getContainer()->get(RtmrReconciliator::class);
        $this->userManager      = self::getContainer()->get(UserManager::class);
        $this->volunteerManager = self::getContainer()->get(VolunteerManager::class);
        $this->badgeManager     = self::getContainer()->get(BadgeManager::class);
        $this->em               = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtures         = new DataFixtures(
            $this->em,
            self::getContainer()->get('security.password_hasher')
        );
    }

    private function makeBadge(string $name) : Badge
    {
        $badge = $this->badgeManager->findOneByName($name);
        if (!$badge) {
            $badge = new Badge();
            $badge->setName($name);
            $badge->setDescription($name);
            $badge->setExternalId('rtmr-test-'.bin2hex(random_bytes(2)));
            $this->badgeManager->save($badge);
        }

        return $badge;
    }

    public function testDisabledVolunteerLosesPrivilegesAndStructures()
    {
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $volunteer->setEnabled(false);
        $user = $data['user'];
        $user->setIsTrusted(true);
        $user->setIsAdmin(true);
        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertFalse($reloaded->isTrusted());
        $this->assertFalse($reloaded->isAdmin());
        $this->assertCount(0, $reloaded->getStructures(false));
    }

    public function testAnnuaireNationalStructureIsPreservedDuringClear()
    {
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $volunteer->setEnabled(false);
        $user = $data['user'];

        $an = $this->fixtures->createStructure(AnnuaireNationalCommand::STRUCTURE_NAME, 'AN-1');
        $an->setShortcut(AnnuaireNationalCommand::STRUCTURE_NAME);
        $user->addStructure($an);

        $this->em->persist($an);
        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $shortcuts = array_map(fn ($s) => $s->getShortcut(), $reloaded->getStructures(false)->toArray());
        $this->assertContains(AnnuaireNationalCommand::STRUCTURE_NAME, $shortcuts);
    }

    public function testInvalidRtmrBadgeStripsAdmin()
    {
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $invalid   = $this->makeBadge(RtmrReconciliator::INVALID_RTMR_BADGE);
        $volunteer->addBadge($invalid);
        $user = $data['user'];
        $user->setIsAdmin(true);

        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertFalse($reloaded->isAdmin());
    }

    public function testValidRtmrCreatesUserIfMissing()
    {
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        // Detach the existing user
        $existingUser = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        if ($existingUser) {
            $this->em->remove($existingUser);
            $this->em->flush();
            $this->em->refresh($volunteer);
        }

        $volunteer->addBadge($this->makeBadge(RtmrReconciliator::RTMR_BADGE));
        $this->em->persist($volunteer);
        $this->em->flush();

        $this->assertNull($this->userManager->findOneByExternalId($volunteer->getExternalId()));

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $user = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertNotNull($user, 'RTMR volunteer must have a RedCall user');
        $this->assertFalse($user->isAdmin(), 'RTMR users must not be admins');
    }

    public function testLockedUserIsLeftUntouchedEvenWhenVolunteerIsDisabled()
    {
        // user.locked = true means an admin manually pinned this user's
        // privileges/structures. Sync must not strip them even when the
        // bound volunteer would normally fire clearPrivilegesIfDecommissioned.
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $volunteer->setEnabled(false); // would normally trigger the clear
        $user = $data['user'];
        $user->setIsTrusted(true);
        $user->setIsAdmin(true);
        $user->setLocked(true); // ← the new contract
        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isTrusted(), 'Locked user must keep trust');
        $this->assertTrue($reloaded->isAdmin(), 'Locked user must keep admin');
        $this->assertGreaterThan(0, $reloaded->getStructures(false)->count(), 'Locked user must keep structures');
    }

    public function testAnnuaireVolunteerIsLeftUntouched()
    {
        // Volunteer whose external_id carries the Annuaire National prefix
        // is owned by another sync path. RtmrReconciliator must early-return
        // even if the volunteer is disabled / has a bound user / etc.
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $volunteer->setExternalId('user-annu-jean-doe-croix-rouge-fr');
        $volunteer->setEnabled(false); // would normally trigger clearPrivilegesIfDecommissioned
        $user = $data['user'];
        // Keep the user the "same person" as the volunteer (shared NIVOL).
        $user->setExternalId('user-annu-jean-doe-croix-rouge-fr');
        $user->setIsTrusted(true);
        $user->setIsAdmin(true);
        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isTrusted(), 'Annuaire-managed volunteer must not have its bound user touched');
        $this->assertTrue($reloaded->isAdmin(), 'Annuaire-managed volunteer must not have its bound user touched');
        $this->assertGreaterThan(0, $reloaded->getStructures(false)->count(), 'Annuaire user structures untouched');
    }

    public function testValidRtmrUserLosesAdminFlag()
    {
        $data = $this->fixtures->createUserWithVolunteerAndStructure();
        /** @var Volunteer $volunteer */
        $volunteer = $data['volunteer'];
        $volunteer->addBadge($this->makeBadge(RtmrReconciliator::RTMR_BADGE));
        $user = $data['user'];
        $user->setIsAdmin(true);

        $this->em->persist($volunteer);
        $this->em->persist($user);
        $this->em->flush();

        $this->reconciliator->reconcile($volunteer);
        $this->em->clear();

        $reloaded = $this->userManager->findOneByExternalId($volunteer->getExternalId());
        $this->assertFalse($reloaded->isAdmin());
    }
}
