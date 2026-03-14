<?php

namespace App\Tests\Entity;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function createUser(
        string $username = 'user@example.com',
        bool $trusted = false,
        bool $admin = false,
        bool $root = false
    ): User {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword('password');
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');
        $user->setIsTrusted($trusted);
        $user->setIsAdmin($admin);
        $user->setIsRoot($root);

        return $user;
    }

    private function createStructure(
        string $name = 'Test Structure',
        string $externalId = 'EXT-001',
        bool $enabled = true,
        ?string $shortcut = null
    ): Structure {
        $structure = new Structure();
        $structure->setName($name);
        $structure->setExternalId($externalId);
        $structure->setEnabled($enabled);
        if ($shortcut) {
            $structure->setShortcut($shortcut);
        }

        return $structure;
    }

    private function createVolunteer(string $firstName = 'Jean', string $lastName = 'Dupont'): Volunteer
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-001');
        $volunteer->setFirstName($firstName);
        $volunteer->setLastName($lastName);

        return $volunteer;
    }

    private function createBadge(string $name, int $renderingPriority = 0, ?Category $category = null): Badge
    {
        $badge = new Badge();
        $badge->setName($name);
        $badge->setExternalId('B-' . $name);
        $badge->setRenderingPriority($renderingPriority);
        if ($category) {
            $badge->setCategory($category);
        }

        return $badge;
    }

    private function createCategory(string $name, int $priority): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setExternalId('CAT-' . $name);
        $category->setPriority($priority);

        return $category;
    }

    // --- setVolunteer ---

    public function testSetVolunteerSetsVolunteer(): void
    {
        $user = $this->createUser();
        $volunteer = $this->createVolunteer();

        $result = $user->setVolunteer($volunteer);

        $this->assertSame($user, $result);
        $this->assertSame($volunteer, $user->getVolunteer());
    }

    public function testSetVolunteerSyncsVolunteerUser(): void
    {
        $user = $this->createUser();
        $volunteer = $this->createVolunteer();

        $user->setVolunteer($volunteer);

        // setVolunteer calls volunteer->setUser($this) internally
        // We need to test the internal user reference on volunteer
        $ref = new \ReflectionProperty(Volunteer::class, 'user');
        $ref->setAccessible(true);
        $this->assertSame($user, $ref->getValue($volunteer));
    }

    public function testSetVolunteerToNull(): void
    {
        $user = $this->createUser();
        $volunteer = $this->createVolunteer();
        $user->setVolunteer($volunteer);

        $user->setVolunteer(null);

        $this->assertNull($user->getVolunteer());
    }

    // --- getStructuresAsList ---

    public function testGetStructuresAsListReturnsAssociativeArray(): void
    {
        $user = $this->createUser();
        $structureA = $this->createStructure('Alpha', 'EXT-A');
        $structureB = $this->createStructure('Beta', 'EXT-B');
        $user->addStructure($structureA);
        $user->addStructure($structureB);

        $list = $user->getStructuresAsList();

        $this->assertArrayHasKey('EXT-A', $list);
        $this->assertArrayHasKey('EXT-B', $list);
        // setName uppercases the name
        $this->assertSame('ALPHA', $list['EXT-A']);
        $this->assertSame('BETA', $list['EXT-B']);
    }

    public function testGetStructuresAsListReturnsEmptyWhenNoStructures(): void
    {
        $user = $this->createUser();

        $this->assertSame([], $user->getStructuresAsList());
    }

    public function testGetStructuresAsListFiltersDisabledStructures(): void
    {
        $user = $this->createUser();
        $enabled = $this->createStructure('Enabled', 'EXT-E', true);
        $disabled = $this->createStructure('Disabled', 'EXT-D', false);
        $user->addStructure($enabled);
        $user->addStructure($disabled);

        $list = $user->getStructuresAsList();

        $this->assertCount(1, $list);
        $this->assertArrayHasKey('EXT-E', $list);
        $this->assertArrayNotHasKey('EXT-D', $list);
    }

    // --- getStructures ---

    public function testGetStructuresReturnsOnlyEnabledByDefault(): void
    {
        $user = $this->createUser();
        $enabled = $this->createStructure('Enabled', 'EXT-E', true);
        $disabled = $this->createStructure('Disabled', 'EXT-D', false);
        $user->addStructure($enabled);
        $user->addStructure($disabled);

        $structures = $user->getStructures();

        $this->assertCount(1, $structures);
        $this->assertTrue($structures->contains($enabled));
        $this->assertFalse($structures->contains($disabled));
    }

    public function testGetStructuresReturnsAllWhenFlagIsFalse(): void
    {
        $user = $this->createUser();
        $enabled = $this->createStructure('Enabled', 'EXT-E', true);
        $disabled = $this->createStructure('Disabled', 'EXT-D', false);
        $user->addStructure($enabled);
        $user->addStructure($disabled);

        $structures = $user->getStructures(false);

        $this->assertCount(2, $structures);
    }

    // --- getStructuresShortcuts ---

    public function testGetStructuresShortcutsReturnsShortcuts(): void
    {
        $user = $this->createUser();
        $structureA = $this->createStructure('Alpha', 'EXT-A', true, 'AL');
        $structureB = $this->createStructure('Beta', 'EXT-B', true, 'BE');

        $ref = new \ReflectionProperty(Structure::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($structureA, 10);
        $ref->setValue($structureB, 20);

        $user->addStructure($structureA);
        $user->addStructure($structureB);

        $shortcuts = $user->getStructuresShortcuts();

        $this->assertSame(['AL', 'BE'], array_values($shortcuts));
        $this->assertArrayHasKey(10, $shortcuts);
        $this->assertArrayHasKey(20, $shortcuts);
    }

    public function testGetStructuresShortcutsSkipsStructuresWithoutShortcut(): void
    {
        $user = $this->createUser();
        $withShortcut = $this->createStructure('Alpha', 'EXT-A', true, 'AL');
        $withoutShortcut = $this->createStructure('Beta', 'EXT-B', true);

        $ref = new \ReflectionProperty(Structure::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($withShortcut, 10);
        $ref->setValue($withoutShortcut, 20);

        $user->addStructure($withShortcut);
        $user->addStructure($withoutShortcut);

        $shortcuts = $user->getStructuresShortcuts();

        $this->assertCount(1, $shortcuts);
        $this->assertArrayHasKey(10, $shortcuts);
    }

    public function testGetStructuresShortcutsReturnsEmptyWhenNoStructures(): void
    {
        $user = $this->createUser();

        $this->assertSame([], $user->getStructuresShortcuts());
    }

    // --- removeStructure ---

    public function testRemoveStructureRemovesExisting(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();
        $user->addStructure($structure);

        $result = $user->removeStructure($structure);

        $this->assertSame($user, $result);
        $this->assertFalse($user->getStructures(false)->contains($structure));
    }

    public function testRemoveStructureDoesNothingWhenAbsent(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();

        $user->removeStructure($structure);

        $this->assertCount(0, $user->getStructures(false));
    }

    // --- updateStructures ---

    public function testUpdateStructuresAddsAllProvided(): void
    {
        $user = $this->createUser();
        $structureA = $this->createStructure('Alpha', 'EXT-A');
        $structureB = $this->createStructure('Beta', 'EXT-B');

        $user->updateStructures([$structureA, $structureB]);

        $this->assertCount(2, $user->getStructures(false));
        $this->assertTrue($user->getStructures(false)->contains($structureA));
        $this->assertTrue($user->getStructures(false)->contains($structureB));
    }

    public function testUpdateStructuresDoesNotDuplicate(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();
        $user->addStructure($structure);

        $user->updateStructures([$structure]);

        $this->assertCount(1, $user->getStructures(false));
    }

    // --- addStructure ---

    public function testAddStructureAddsNew(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();

        $result = $user->addStructure($structure);

        $this->assertSame($user, $result);
        $this->assertCount(1, $user->getStructures(false));
        $this->assertTrue($user->getStructures(false)->contains($structure));
    }

    public function testAddStructureDoesNotAddDuplicate(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();

        $user->addStructure($structure);
        $user->addStructure($structure);

        $this->assertCount(1, $user->getStructures(false));
    }

    // --- hasCommonStructure ---

    public function testHasCommonStructureReturnsTrueWhenAdmin(): void
    {
        $user = $this->createUser('admin@test.com', true, true);
        $structure = $this->createStructure();

        // Admin should always return true, even without the structure
        $this->assertTrue($user->hasCommonStructure([$structure]));
    }

    public function testHasCommonStructureReturnsTrueWhenShared(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();
        $user->addStructure($structure);

        $this->assertTrue($user->hasCommonStructure([$structure]));
    }

    public function testHasCommonStructureReturnsFalseWhenNoShared(): void
    {
        $user = $this->createUser();
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');
        $user->addStructure($structureA);

        $this->assertFalse($user->hasCommonStructure([$structureB]));
    }

    public function testHasCommonStructureReturnsFalseWithEmptyArray(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->hasCommonStructure([]));
    }

    // --- getCommonStructures ---

    public function testGetCommonStructuresReturnsAllWhenAdmin(): void
    {
        $user = $this->createUser('admin@test.com', true, true);
        $structureA = $this->createStructure('Alpha', 'EXT-A');
        $structureB = $this->createStructure('Beta', 'EXT-B');

        $common = $user->getCommonStructures([$structureA, $structureB]);

        $this->assertCount(2, $common);
    }

    public function testGetCommonStructuresReturnsAllWhenAdminSortedAlphabetically(): void
    {
        $user = $this->createUser('admin@test.com', true, true);
        $beta = $this->createStructure('Beta', 'EXT-B');
        $alpha = $this->createStructure('Alpha', 'EXT-A');

        $common = $user->getCommonStructures([$beta, $alpha]);

        // Should be sorted alphabetically by display name
        $this->assertSame($alpha, $common[0]);
        $this->assertSame($beta, $common[1]);
    }

    public function testGetCommonStructuresReturnsOnlySharedForNonAdmin(): void
    {
        $user = $this->createUser();
        $structureA = $this->createStructure('Alpha', 'EXT-A');
        $structureB = $this->createStructure('Beta', 'EXT-B');
        $structureC = $this->createStructure('Gamma', 'EXT-C');
        $user->addStructure($structureA);
        $user->addStructure($structureC);

        $common = $user->getCommonStructures([$structureA, $structureB, $structureC]);

        $this->assertCount(2, $common);
        $this->assertContains($structureA, $common);
        $this->assertContains($structureC, $common);
    }

    public function testGetCommonStructuresReturnsSortedForNonAdmin(): void
    {
        $user = $this->createUser();
        $gamma = $this->createStructure('Gamma', 'EXT-C');
        $alpha = $this->createStructure('Alpha', 'EXT-A');
        $user->addStructure($gamma);
        $user->addStructure($alpha);

        $common = $user->getCommonStructures([$gamma, $alpha]);

        $this->assertSame($alpha, $common[0]);
        $this->assertSame($gamma, $common[1]);
    }

    public function testGetCommonStructuresReturnsEmptyWhenNoShared(): void
    {
        $user = $this->createUser();
        $structure = $this->createStructure();

        $common = $user->getCommonStructures([$structure]);

        $this->assertSame([], $common);
    }

    // --- getMainStructure ---

    public function testGetMainStructureReturnsHighestInHierarchy(): void
    {
        $user = $this->createUser();
        $grandparent = $this->createStructure('GP', 'EXT-GP');
        $parent = $this->createStructure('P', 'EXT-P');
        $child = $this->createStructure('C', 'EXT-C');

        $parent->setParentStructure($grandparent);
        $child->setParentStructure($parent);

        $user->addStructure($child);
        $user->addStructure($parent);

        $this->assertSame($parent, $user->getMainStructure());
    }

    public function testGetMainStructureReturnsNullWhenEmpty(): void
    {
        $user = $this->createUser();

        $this->assertNull($user->getMainStructure());
    }

    public function testGetMainStructureReturnsAlphabeticallyFirstAtSameLevel(): void
    {
        $user = $this->createUser();
        $alpha = $this->createStructure('Alpha', 'EXT-A');
        $beta = $this->createStructure('Beta', 'EXT-B');

        $user->addStructure($beta);
        $user->addStructure($alpha);

        $this->assertSame($alpha, $user->getMainStructure());
    }

    public function testGetMainStructureSkipsDisabledStructures(): void
    {
        $user = $this->createUser();
        $enabled = $this->createStructure('Beta', 'EXT-B', true);
        $disabled = $this->createStructure('Alpha', 'EXT-A', false);

        $user->addStructure($disabled);
        $user->addStructure($enabled);

        // Alpha is alphabetically first but disabled, getStructures(true) filters it
        $this->assertSame($enabled, $user->getMainStructure());
    }

    // --- getDisplayName ---

    public function testGetDisplayNameWithVolunteer(): void
    {
        $user = $this->createUser();
        $volunteer = $this->createVolunteer('Jean', 'Dupont');
        $user->setVolunteer($volunteer);

        $this->assertSame('Jean Dupont', $user->getDisplayName());
    }

    public function testGetDisplayNameWithoutVolunteer(): void
    {
        $user = $this->createUser('user@example.com');

        $this->assertSame('user@example.com', $user->getDisplayName());
    }

    public function testGetDisplayNameWithVolunteerWithoutFirstName(): void
    {
        $user = $this->createUser('user@example.com');
        $volunteer = new Volunteer();
        $volunteer->setExternalId('VOL-001');
        $volunteer->setLastName('Dupont');
        // No first name
        $user->setVolunteer($volunteer);

        // Falls back to username since firstName is null
        $this->assertSame('user@example.com', $user->getDisplayName());
    }

    // --- getRoles ---

    public function testGetRolesReturnsBaseRoleForRegularUser(): void
    {
        $user = $this->createUser();

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertNotContains('ROLE_TRUSTED', $roles);
        $this->assertNotContains('ROLE_ADMIN', $roles);
        $this->assertNotContains('ROLE_ROOT', $roles);
    }

    public function testGetRolesIncludesTrusted(): void
    {
        $user = $this->createUser('user@test.com', true);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_TRUSTED', $roles);
    }

    public function testGetRolesIncludesAdmin(): void
    {
        $user = $this->createUser('user@test.com', true, true);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesIncludesRoot(): void
    {
        $user = $this->createUser('user@test.com', true, true, true);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ROOT', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_TRUSTED', $roles);
    }

    public function testGetRolesDoesNotIncludeRootWhenNotRoot(): void
    {
        $user = $this->createUser('user@test.com', true, true, false);

        $roles = $user->getRoles();

        $this->assertNotContains('ROLE_ROOT', $roles);
    }

    // --- getSortedFavoriteBadges ---

    public function testGetSortedFavoriteBadgesReturnsSorted(): void
    {
        $user = $this->createUser();

        $catA = $this->createCategory('CatA', 10);
        $catB = $this->createCategory('CatB', 1);

        $badgeA = $this->createBadge('Alpha', 100, $catA);
        $badgeB = $this->createBadge('Beta', 50, $catB);

        $user->addFavoriteBadge($badgeA);
        $user->addFavoriteBadge($badgeB);

        $sorted = $user->getSortedFavoriteBadges();

        // catB has priority 1 < catA priority 10, so badgeB comes first
        $this->assertSame($badgeB, $sorted[0]);
        $this->assertSame($badgeA, $sorted[1]);
    }

    public function testGetSortedFavoriteBadgesReturnsEmptyWhenNone(): void
    {
        $user = $this->createUser();

        $this->assertSame([], $user->getSortedFavoriteBadges());
    }

    public function testGetSortedFavoriteBadgesSameCategorySortsByRendering(): void
    {
        $user = $this->createUser();
        $cat = $this->createCategory('Cat', 5);

        $badgeA = $this->createBadge('Alpha', 100, $cat);
        $badgeB = $this->createBadge('Beta', 50, $cat);

        $user->addFavoriteBadge($badgeA);
        $user->addFavoriteBadge($badgeB);

        $sorted = $user->getSortedFavoriteBadges();

        // Same category, so sort by rendering priority: 50 < 100
        $this->assertSame($badgeB, $sorted[0]);
        $this->assertSame($badgeA, $sorted[1]);
    }

    // --- addFavoriteBadge ---

    public function testAddFavoriteBadgeAddsNew(): void
    {
        $user = $this->createUser();
        $badge = $this->createBadge('Test');

        $result = $user->addFavoriteBadge($badge);

        $this->assertSame($user, $result);
        $this->assertCount(1, $user->getFavoriteBadges());
        $this->assertTrue($user->getFavoriteBadges()->contains($badge));
    }

    public function testAddFavoriteBadgeDoesNotAddDuplicate(): void
    {
        $user = $this->createUser();
        $badge = $this->createBadge('Test');

        $user->addFavoriteBadge($badge);
        $user->addFavoriteBadge($badge);

        $this->assertCount(1, $user->getFavoriteBadges());
    }

    public function testRemoveFavoriteBadgeRemovesExisting(): void
    {
        $user = $this->createUser();
        $badge = $this->createBadge('Test');
        $user->addFavoriteBadge($badge);

        $result = $user->removeFavoriteBadge($badge);

        $this->assertSame($user, $result);
        $this->assertCount(0, $user->getFavoriteBadges());
    }
}
