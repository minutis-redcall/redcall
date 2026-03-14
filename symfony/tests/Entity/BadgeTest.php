<?php

namespace App\Tests\Entity;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Volunteer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class BadgeTest extends TestCase
{
    private function createBadge(string $name, ?string $description = null, ?int $id = null): Badge
    {
        $badge = new Badge();
        $badge->setName($name);
        $badge->setExternalId('ext-' . ($id ?? random_int(1, 999999)));
        if ($description) {
            $badge->setDescription($description);
        }
        if (null !== $id) {
            $ref = new \ReflectionProperty(Badge::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($badge, $id);
        }

        return $badge;
    }

    private function createVolunteer(bool $enabled = true): Volunteer
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('vol-' . random_int(1, 999999));
        $volunteer->setEnabled($enabled);

        return $volunteer;
    }

    // --- getVolunteers ---

    public function testGetVolunteersOnlyEnabledByDefault(): void
    {
        $badge = $this->createBadge('Test');
        $enabledVolunteer = $this->createVolunteer(true);
        $disabledVolunteer = $this->createVolunteer(false);

        $badge->addVolunteer($enabledVolunteer);
        $badge->addVolunteer($disabledVolunteer);

        $result = $badge->getVolunteers();
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($enabledVolunteer));
    }

    public function testGetVolunteersAllWhenFlagIsFalse(): void
    {
        $badge = $this->createBadge('Test');
        $enabledVolunteer = $this->createVolunteer(true);
        $disabledVolunteer = $this->createVolunteer(false);

        $badge->addVolunteer($enabledVolunteer);
        $badge->addVolunteer($disabledVolunteer);

        $result = $badge->getVolunteers(false);
        $this->assertCount(2, $result);
    }

    public function testGetVolunteersEmpty(): void
    {
        $badge = $this->createBadge('Test');
        $this->assertCount(0, $badge->getVolunteers());
    }

    // --- addVolunteer / removeVolunteer ---

    public function testAddVolunteer(): void
    {
        $badge = $this->createBadge('Test');
        $volunteer = $this->createVolunteer();

        $result = $badge->addVolunteer($volunteer);

        $this->assertSame($badge, $result);
        $this->assertCount(1, $badge->getVolunteers(false));
    }

    public function testAddVolunteerDoesNotAddDuplicate(): void
    {
        $badge = $this->createBadge('Test');
        $volunteer = $this->createVolunteer();

        $badge->addVolunteer($volunteer);
        $badge->addVolunteer($volunteer);

        $this->assertCount(1, $badge->getVolunteers(false));
    }

    public function testRemoveVolunteer(): void
    {
        $badge = $this->createBadge('Test');
        $volunteer = $this->createVolunteer();
        $badge->addVolunteer($volunteer);

        $result = $badge->removeVolunteer($volunteer);

        $this->assertSame($badge, $result);
        $this->assertCount(0, $badge->getVolunteers(false));
    }

    public function testRemoveVolunteerDoesNothingWhenAbsent(): void
    {
        $badge = $this->createBadge('Test');
        $volunteerA = $this->createVolunteer();
        $volunteerB = $this->createVolunteer();
        $badge->addVolunteer($volunteerA);

        $badge->removeVolunteer($volunteerB);

        $this->assertCount(1, $badge->getVolunteers(false));
    }

    // --- getSynonym ---

    public function testGetSynonymReturnsNullWhenNone(): void
    {
        $badge = $this->createBadge('Test');
        $this->assertNull($badge->getSynonym());
    }

    public function testGetSynonymReturnsDirect(): void
    {
        $badge = $this->createBadge('Test');
        $synonym = $this->createBadge('Synonym');

        $badge->setSynonym($synonym);

        $this->assertSame($synonym, $badge->getSynonym());
    }

    public function testGetSynonymFollowsOneChain(): void
    {
        $badgeA = $this->createBadge('A');
        $badgeB = $this->createBadge('B');
        $badgeC = $this->createBadge('C');

        // A -> B -> C
        $badgeA->setSynonym($badgeB);
        $badgeB->setSynonym($badgeC);

        // getSynonym on A should resolve to C (follows one level from B)
        $this->assertSame($badgeC, $badgeA->getSynonym());
    }

    // --- getChildren ---

    public function testGetChildrenEmpty(): void
    {
        $badge = $this->createBadge('Parent');
        $this->assertCount(0, $badge->getChildren());
    }

    public function testGetChildrenOnlyEnabledByDefault(): void
    {
        $parent = $this->createBadge('Parent');
        $enabledChild = $this->createBadge('EnabledChild');
        $enabledChild->setEnabled(true);
        $disabledChild = $this->createBadge('DisabledChild');
        $disabledChild->setEnabled(false);

        $parent->addChild($enabledChild);
        $parent->addChild($disabledChild);

        $result = $parent->getChildren();
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($enabledChild));
    }

    public function testGetChildrenAllWhenFlagIsFalse(): void
    {
        $parent = $this->createBadge('Parent');
        $enabledChild = $this->createBadge('EnabledChild');
        $disabledChild = $this->createBadge('DisabledChild');
        $disabledChild->setEnabled(false);

        $parent->addChild($enabledChild);
        $parent->addChild($disabledChild);

        $this->assertCount(2, $parent->getChildren(false));
    }

    // --- addChild / removeChild ---

    public function testAddChildSetsParent(): void
    {
        $parent = $this->createBadge('Parent');
        $child = $this->createBadge('Child');

        $result = $parent->addChild($child);

        $this->assertSame($parent, $result);
        $this->assertSame($parent, $child->getParent());
        $this->assertCount(1, $parent->getChildren(false));
    }

    public function testAddChildDoesNotAddDuplicate(): void
    {
        $parent = $this->createBadge('Parent');
        $child = $this->createBadge('Child');

        $parent->addChild($child);
        $parent->addChild($child);

        $this->assertCount(1, $parent->getChildren(false));
    }

    public function testRemoveChildUnsetsParent(): void
    {
        $parent = $this->createBadge('Parent');
        $child = $this->createBadge('Child');
        $parent->addChild($child);

        $result = $parent->removeChild($child);

        $this->assertSame($parent, $result);
        $this->assertNull($child->getParent());
        $this->assertCount(0, $parent->getChildren(false));
    }

    public function testRemoveChildDoesNothingWhenAbsent(): void
    {
        $parent = $this->createBadge('Parent');
        $childA = $this->createBadge('ChildA');
        $childB = $this->createBadge('ChildB');
        $parent->addChild($childA);

        $parent->removeChild($childB);

        $this->assertCount(1, $parent->getChildren(false));
    }

    public function testRemoveChildDoesNotUnsetParentIfAlreadyChanged(): void
    {
        $parent = $this->createBadge('Parent');
        $otherParent = $this->createBadge('OtherParent');
        $child = $this->createBadge('Child');
        $parent->addChild($child);

        // Manually reassign parent before removal
        $child->setParent($otherParent);
        $parent->removeChild($child);

        // Parent was already changed, so removeChild should not null it
        $this->assertSame($otherParent, $child->getParent());
    }

    // --- getFullName ---

    public function testGetFullNameWithoutDescription(): void
    {
        $badge = $this->createBadge('First Aid');
        $this->assertSame('First Aid', $badge->getFullName());
    }

    public function testGetFullNameWithDescription(): void
    {
        $badge = $this->createBadge('PSE1', 'Premiers Secours en Equipe');
        $this->assertSame('PSE1 (Premiers Secours en Equipe)', $badge->getFullName());
    }

    // --- toSearchResults ---

    public function testToSearchResults(): void
    {
        $badge = $this->createBadge('PSE1', 'Level 1', 42);

        $result = $badge->toSearchResults();

        $this->assertIsArray($result);
        $this->assertSame('42', $result['id']);
        $this->assertSame('PSE1 (Level 1)', $result['name']);
    }

    public function testToSearchResultsEscapesHtml(): void
    {
        $badge = $this->createBadge('<script>alert("xss")</script>', null, 1);

        $result = $badge->toSearchResults();

        $this->assertStringNotContainsString('<script>', $result['name']);
    }

    // --- getSynonyms ---

    public function testGetSynonymsEmpty(): void
    {
        $badge = $this->createBadge('Test');
        $this->assertCount(0, $badge->getSynonyms());
    }

    public function testGetSynonymsOnlyEnabledByDefault(): void
    {
        $main = $this->createBadge('Main');
        $enabledSyn = $this->createBadge('EnabledSyn');
        $enabledSyn->setEnabled(true);
        $disabledSyn = $this->createBadge('DisabledSyn');
        $disabledSyn->setEnabled(false);

        $main->addSynonym($enabledSyn);
        $main->addSynonym($disabledSyn);

        $result = $main->getSynonyms();
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($enabledSyn));
    }

    public function testGetSynonymsAllWhenFlagIsFalse(): void
    {
        $main = $this->createBadge('Main');
        $enabledSyn = $this->createBadge('EnabledSyn');
        $disabledSyn = $this->createBadge('DisabledSyn');
        $disabledSyn->setEnabled(false);

        $main->addSynonym($enabledSyn);
        $main->addSynonym($disabledSyn);

        $this->assertCount(2, $main->getSynonyms(false));
    }

    // --- addSynonym / removeSynonym ---

    public function testAddSynonymSetsSynonymOnChild(): void
    {
        $main = $this->createBadge('Main');
        $syn = $this->createBadge('Synonym');

        $result = $main->addSynonym($syn);

        $this->assertSame($main, $result);
        $this->assertSame($main, $syn->getSynonym());
        $this->assertCount(1, $main->getSynonyms(false));
    }

    public function testAddSynonymDoesNotAddDuplicate(): void
    {
        $main = $this->createBadge('Main');
        $syn = $this->createBadge('Synonym');

        $main->addSynonym($syn);
        $main->addSynonym($syn);

        $this->assertCount(1, $main->getSynonyms(false));
    }

    public function testRemoveSynonymUnsetsSynonymOnChild(): void
    {
        $main = $this->createBadge('Main');
        $syn = $this->createBadge('Synonym');
        $main->addSynonym($syn);

        $result = $main->removeSynonym($syn);

        $this->assertSame($main, $result);
        $this->assertNull($syn->getSynonym());
        $this->assertCount(0, $main->getSynonyms(false));
    }

    public function testRemoveSynonymDoesNothingWhenAbsent(): void
    {
        $main = $this->createBadge('Main');
        $synA = $this->createBadge('SynA');
        $synB = $this->createBadge('SynB');
        $main->addSynonym($synA);

        $main->removeSynonym($synB);

        $this->assertCount(1, $main->getSynonyms(false));
    }

    public function testRemoveSynonymDoesNotUnsetIfAlreadyChanged(): void
    {
        $main = $this->createBadge('Main');
        $other = $this->createBadge('Other');
        $syn = $this->createBadge('Synonym');
        $main->addSynonym($syn);

        // Reassign synonym before removal
        $syn->setSynonym($other);
        $main->removeSynonym($syn);

        // getSynonym follows one level of chain: other has no synonym => returns $other
        $this->assertSame($other, $syn->getSynonym());
    }

    // --- canBeRemoved ---

    public function testCanBeRemovedWhenUnlocked(): void
    {
        $badge = $this->createBadge('Test');
        $badge->setLocked(false);

        $this->assertTrue($badge->canBeRemoved());
    }

    public function testCanBeRemovedWhenLocked(): void
    {
        $badge = $this->createBadge('Test');
        $badge->setLocked(true);

        $this->assertFalse($badge->canBeRemoved());
    }

    // --- getCoveringBadges ---

    public function testGetCoveringBadgesNoParent(): void
    {
        $badge = $this->createBadge('Root');
        $this->assertSame([], $badge->getCoveringBadges());
    }

    public function testGetCoveringBadgesWithChain(): void
    {
        $grandparent = $this->createBadge('GP', null, 1);
        $parent = $this->createBadge('P', null, 2);
        $child = $this->createBadge('C', null, 3);

        $grandparent->addChild($parent);
        $parent->addChild($child);

        $covering = $child->getCoveringBadges();
        $this->assertCount(2, $covering);
        $this->assertSame($grandparent, $covering[0]);
        $this->assertSame($parent, $covering[1]);
    }

    public function testGetCoveringBadgesWithStop(): void
    {
        $grandparent = $this->createBadge('GP', null, 1);
        $parent = $this->createBadge('P', null, 2);
        $child = $this->createBadge('C', null, 3);

        $grandparent->addChild($parent);
        $parent->addChild($child);

        // Stop at grandparent's id, so it should not be included
        $covering = $child->getCoveringBadges(1);
        $this->assertCount(1, $covering);
        $this->assertSame($parent, $covering[0]);
    }

    // --- getCoveredBadges ---

    public function testGetCoveredBadgesNoChildren(): void
    {
        $badge = $this->createBadge('Leaf');
        $this->assertSame([], $badge->getCoveredBadges());
    }

    public function testGetCoveredBadgesRecursive(): void
    {
        $root = $this->createBadge('Root');
        $child1 = $this->createBadge('Child1');
        $child2 = $this->createBadge('Child2');
        $grandchild = $this->createBadge('Grandchild');

        $root->addChild($child1);
        $root->addChild($child2);
        $child1->addChild($grandchild);

        $covered = $root->getCoveredBadges();
        $this->assertCount(3, $covered);
        $this->assertContains($child1, $covered);
        $this->assertContains($grandchild, $covered);
        $this->assertContains($child2, $covered);
    }

    public function testGetCoveredBadgesExcludesDisabled(): void
    {
        $root = $this->createBadge('Root');
        $enabledChild = $this->createBadge('Enabled');
        $disabledChild = $this->createBadge('Disabled');
        $disabledChild->setEnabled(false);

        $root->addChild($enabledChild);
        $root->addChild($disabledChild);

        // getCoveredBadges uses getChildren() which defaults to onlyEnabled=true
        $covered = $root->getCoveredBadges();
        $this->assertCount(1, $covered);
        $this->assertContains($enabledChild, $covered);
    }

    // --- isUsable ---

    public function testIsUsableWithNoSynonym(): void
    {
        $badge = $this->createBadge('Test');
        $this->assertTrue($badge->isUsable());
    }

    public function testIsUsableWithSynonym(): void
    {
        $badge = $this->createBadge('Test');
        $main = $this->createBadge('Main');
        $badge->setSynonym($main);

        $this->assertFalse($badge->isUsable());
    }

    // --- validate (tests isParentLooping indirectly) ---

    public function testValidateNoViolationsWhenValid(): void
    {
        $badge = $this->createBadge('Test', null, 1);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $badge->validate($context, null);
    }

    public function testValidateDetectsParentLoop(): void
    {
        // Create A -> B -> A loop
        $badgeA = $this->createBadge('A', null, 1);
        $badgeB = $this->createBadge('B', null, 2);

        $badgeA->setParent($badgeB);
        $badgeB->setParent($badgeA);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturnSelf();
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('form.badge.errors.parent.loop', $this->anything())
            ->willReturn($violationBuilder);

        $badgeA->validate($context, null);
    }

    public function testValidateDetectsParentIsSynonym(): void
    {
        $parent = $this->createBadge('Parent', null, 1);
        $mainBadge = $this->createBadge('Main', null, 2);
        $child = $this->createBadge('Child', null, 3);

        // Parent is a synonym of Main
        $parent->setSynonym($mainBadge);
        $child->setParent($parent);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturnSelf();
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('form.badge.errors.parent.synonym', $this->anything())
            ->willReturn($violationBuilder);

        $child->validate($context, null);
    }

    public function testValidateDetectsVisibleSynonym(): void
    {
        $main = $this->createBadge('Main', null, 1);
        $syn = $this->createBadge('Syn', null, 2);
        $syn->setVisibility(true);

        $main->addSynonym($syn);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturnSelf();
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('form.badge.errors.synonym.visible', $this->anything())
            ->willReturn($violationBuilder);

        $main->validate($context, null);
    }

    public function testValidateDetectsSynonymHasSynonyms(): void
    {
        $main = $this->createBadge('Main', null, 1);
        $syn = $this->createBadge('Syn', null, 2);
        $syn->setVisibility(false);
        $subSyn = $this->createBadge('SubSyn', null, 3);
        $subSyn->setVisibility(false);

        $main->addSynonym($syn);
        $syn->addSynonym($subSyn);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturnSelf();
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->atLeastOnce())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->atLeastOnce())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $main->validate($context, null);
    }

    public function testValidateDetectsSynonymHasParent(): void
    {
        $main = $this->createBadge('Main', null, 1);
        $syn = $this->createBadge('Syn', null, 2);
        $syn->setVisibility(false);
        $parentBadge = $this->createBadge('ParentBadge', null, 3);

        $syn->setParent($parentBadge);
        $main->addSynonym($syn);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturnSelf();
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('form.badge.errors.synonym.has_parent', $this->anything())
            ->willReturn($violationBuilder);

        $main->validate($context, null);
    }

    // --- sortBadges ---

    public function testSortBadgesByCategoryPriority(): void
    {
        $catA = new Category();
        $catA->setName('CatA');
        $catA->setExternalId('ext-cat-a');
        $catA->setPriority(1);

        $catB = new Category();
        $catB->setName('CatB');
        $catB->setExternalId('ext-cat-b');
        $catB->setPriority(2);

        $badgeA = $this->createBadge('A');
        $badgeA->setCategory($catA);
        $badgeA->setRenderingPriority(100);

        $badgeB = $this->createBadge('B');
        $badgeB->setCategory($catB);
        $badgeB->setRenderingPriority(0);

        // catA has lower priority => A comes first
        $this->assertLessThan(0, Badge::sortBadges($badgeA, $badgeB));
        $this->assertGreaterThan(0, Badge::sortBadges($badgeB, $badgeA));
    }

    public function testSortBadgesCategoryVsNoCategory(): void
    {
        $cat = new Category();
        $cat->setName('Cat');
        $cat->setExternalId('ext-cat');
        $cat->setPriority(1);

        $badgeWithCat = $this->createBadge('WithCat');
        $badgeWithCat->setCategory($cat);

        $badgeNoCat = $this->createBadge('NoCat');

        // Badge with category comes first
        $this->assertLessThan(0, Badge::sortBadges($badgeWithCat, $badgeNoCat));
        $this->assertGreaterThan(0, Badge::sortBadges($badgeNoCat, $badgeWithCat));
    }

    public function testSortBadgesNoCategoryFallsBackToRenderingPriority(): void
    {
        $badgeA = $this->createBadge('A');
        $badgeA->setRenderingPriority(10);

        $badgeB = $this->createBadge('B');
        $badgeB->setRenderingPriority(20);

        $this->assertLessThan(0, Badge::sortBadges($badgeA, $badgeB));
        $this->assertGreaterThan(0, Badge::sortBadges($badgeB, $badgeA));
    }

    public function testSortBadgesSameCategorySamePriorityFallsBackToRenderingPriority(): void
    {
        $cat = new Category();
        $cat->setName('Cat');
        $cat->setExternalId('ext-cat');
        $cat->setPriority(5);

        $badgeA = $this->createBadge('A');
        $badgeA->setCategory($cat);
        $badgeA->setRenderingPriority(10);

        $badgeB = $this->createBadge('B');
        $badgeB->setCategory($cat);
        $badgeB->setRenderingPriority(20);

        $this->assertLessThan(0, Badge::sortBadges($badgeA, $badgeB));
        $this->assertGreaterThan(0, Badge::sortBadges($badgeB, $badgeA));
    }

    public function testSortBadgesEqualReturnsZero(): void
    {
        $cat = new Category();
        $cat->setName('Cat');
        $cat->setExternalId('ext-cat');
        $cat->setPriority(5);

        $badgeA = $this->createBadge('A');
        $badgeA->setCategory($cat);
        $badgeA->setRenderingPriority(10);

        $badgeB = $this->createBadge('B');
        $badgeB->setCategory($cat);
        $badgeB->setRenderingPriority(10);

        $this->assertSame(0, Badge::sortBadges($badgeA, $badgeB));
    }

    public function testSortBadgesBothNoCategorySameRenderingPriority(): void
    {
        $badgeA = $this->createBadge('A');
        $badgeA->setRenderingPriority(5);

        $badgeB = $this->createBadge('B');
        $badgeB->setRenderingPriority(5);

        $this->assertSame(0, Badge::sortBadges($badgeA, $badgeB));
    }
}
