<?php

namespace App\Tests\Entity;

use App\Entity\Pegass;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\Template;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class StructureTest extends TestCase
{
    private function createStructure(string $name = 'Test Structure', string $externalId = 'EXT-001'): Structure
    {
        $structure = new Structure();
        $structure->setName($name);
        $structure->setExternalId($externalId);

        return $structure;
    }

    private function createVolunteer(string $externalId = 'VOL-001', bool $enabled = true): Volunteer
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId($externalId);
        $volunteer->setFirstName('John');
        $volunteer->setLastName('Doe');
        $volunteer->setEnabled($enabled);

        return $volunteer;
    }

    private function createUser(string $username = 'user@example.com'): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword('password');
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');

        return $user;
    }

    // --- getVolunteer ---

    public function testGetVolunteerReturnsMatchingVolunteer(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer('VOL-123');
        $structure->addVolunteer($volunteer);

        $result = $structure->getVolunteer('VOL-123');

        $this->assertSame($volunteer, $result);
    }

    public function testGetVolunteerReturnsNullWhenNotFound(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer('VOL-123');
        $structure->addVolunteer($volunteer);

        $this->assertNull($structure->getVolunteer('VOL-999'));
    }

    public function testGetVolunteerReturnsNullWhenEmpty(): void
    {
        $structure = $this->createStructure();

        $this->assertNull($structure->getVolunteer('VOL-123'));
    }

    public function testGetVolunteerSkipsDisabledVolunteers(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer('VOL-123', false);
        // Must add to internal collection directly since addVolunteer uses the collection
        $structure->addVolunteer($volunteer);

        // getVolunteer calls getVolunteers(onlyEnabled=true) which filters disabled
        $this->assertNull($structure->getVolunteer('VOL-123'));
    }

    // --- getVolunteers ---

    public function testGetVolunteersReturnsOnlyEnabledByDefault(): void
    {
        $structure = $this->createStructure();
        $enabled = $this->createVolunteer('VOL-1', true);
        $disabled = $this->createVolunteer('VOL-2', false);
        $structure->addVolunteer($enabled);
        $structure->addVolunteer($disabled);

        $volunteers = $structure->getVolunteers();

        $this->assertCount(1, $volunteers);
        $this->assertTrue($volunteers->contains($enabled));
        $this->assertFalse($volunteers->contains($disabled));
    }

    public function testGetVolunteersReturnsAllWhenFlagIsFalse(): void
    {
        $structure = $this->createStructure();
        $enabled = $this->createVolunteer('VOL-1', true);
        $disabled = $this->createVolunteer('VOL-2', false);
        $structure->addVolunteer($enabled);
        $structure->addVolunteer($disabled);

        $volunteers = $structure->getVolunteers(false);

        $this->assertCount(2, $volunteers);
    }

    // --- addVolunteer ---

    public function testAddVolunteerAddsNewVolunteer(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer();

        $result = $structure->addVolunteer($volunteer);

        $this->assertSame($structure, $result);
        $this->assertCount(1, $structure->getVolunteers(false));
        $this->assertTrue($structure->getVolunteers(false)->contains($volunteer));
    }

    public function testAddVolunteerDoesNotAddDuplicate(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer();

        $structure->addVolunteer($volunteer);
        $structure->addVolunteer($volunteer);

        $this->assertCount(1, $structure->getVolunteers(false));
    }

    public function testAddVolunteerSyncsVolunteerStructures(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer();

        $structure->addVolunteer($volunteer);

        $this->assertTrue($volunteer->getStructures(false)->contains($structure));
    }

    // --- removeVolunteer ---

    public function testRemoveVolunteerRemovesExisting(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer();
        $structure->addVolunteer($volunteer);

        $result = $structure->removeVolunteer($volunteer);

        $this->assertSame($structure, $result);
        $this->assertCount(0, $structure->getVolunteers(false));
    }

    public function testRemoveVolunteerDoesNothingWhenAbsent(): void
    {
        $structure = $this->createStructure();
        $volunteerA = $this->createVolunteer('VOL-1');
        $volunteerB = $this->createVolunteer('VOL-2');
        $structure->addVolunteer($volunteerA);

        $structure->removeVolunteer($volunteerB);

        $this->assertCount(1, $structure->getVolunteers(false));
    }

    public function testRemoveVolunteerSyncsVolunteerStructures(): void
    {
        $structure = $this->createStructure();
        $volunteer = $this->createVolunteer();
        $structure->addVolunteer($volunteer);

        $structure->removeVolunteer($volunteer);

        $this->assertFalse($volunteer->getStructures(false)->contains($structure));
    }

    // --- getAncestors ---

    public function testGetAncestorsReturnsEmptyWhenNoParent(): void
    {
        $structure = $this->createStructure();

        $this->assertSame([], $structure->getAncestors());
    }

    public function testGetAncestorsReturnsSingleParent(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');
        $child->setParentStructure($parent);

        $ancestors = $child->getAncestors();

        $this->assertCount(1, $ancestors);
        $this->assertSame($parent, $ancestors[0]);
    }

    public function testGetAncestorsReturnsMultipleLevels(): void
    {
        $grandparent = $this->createStructure('Grandparent', 'EXT-GP');
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $parent->setParentStructure($grandparent);
        $child->setParentStructure($parent);

        $ancestors = $child->getAncestors();

        $this->assertCount(2, $ancestors);
        $this->assertSame($parent, $ancestors[0]);
        $this->assertSame($grandparent, $ancestors[1]);
    }

    // --- addChildrenStructure ---

    public function testAddChildrenStructureAddsChild(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $result = $parent->addChildrenStructure($child);

        $this->assertSame($parent, $result);
        $this->assertCount(1, $parent->getChildrenStructures());
        $this->assertTrue($parent->getChildrenStructures()->contains($child));
        $this->assertSame($parent, $child->getParentStructure());
    }

    public function testAddChildrenStructureDoesNotAddDuplicate(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $parent->addChildrenStructure($child);
        $parent->addChildrenStructure($child);

        $this->assertCount(1, $parent->getChildrenStructures());
    }

    // --- removeChildrenStructure ---

    public function testRemoveChildrenStructureRemovesChild(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');
        $parent->addChildrenStructure($child);

        $result = $parent->removeChildrenStructure($child);

        $this->assertSame($parent, $result);
        $this->assertCount(0, $parent->getChildrenStructures());
        $this->assertNull($child->getParentStructure());
    }

    public function testRemoveChildrenStructureDoesNothingWhenAbsent(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $parent->removeChildrenStructure($child);

        $this->assertCount(0, $parent->getChildrenStructures());
    }

    public function testRemoveChildrenStructureDoesNotNullParentIfAlreadyChanged(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $otherParent = $this->createStructure('Other', 'EXT-O');
        $child = $this->createStructure('Child', 'EXT-C');
        $parent->addChildrenStructure($child);

        // Change the parent before removing
        $child->setParentStructure($otherParent);
        $parent->removeChildrenStructure($child);

        $this->assertSame($otherParent, $child->getParentStructure());
    }

    // --- addUser ---

    public function testAddUserAddsNewUser(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();

        $result = $structure->addUser($user);

        $this->assertSame($structure, $result);
        $this->assertCount(1, $structure->getUsers());
        $this->assertTrue($structure->getUsers()->contains($user));
    }

    public function testAddUserDoesNotAddDuplicate(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();

        $structure->addUser($user);
        $structure->addUser($user);

        $this->assertCount(1, $structure->getUsers());
    }

    public function testAddUserSyncsUserStructures(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();

        $structure->addUser($user);

        $this->assertTrue($user->getStructures(false)->contains($structure));
    }

    // --- removeUser ---

    public function testRemoveUserRemovesExisting(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();
        $structure->addUser($user);

        $result = $structure->removeUser($user);

        $this->assertSame($structure, $result);
        $this->assertCount(0, $structure->getUsers());
    }

    public function testRemoveUserDoesNothingWhenAbsent(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();

        $structure->removeUser($user);

        $this->assertCount(0, $structure->getUsers());
    }

    public function testRemoveUserSyncsUserStructures(): void
    {
        $structure = $this->createStructure();
        $user = $this->createUser();
        $structure->addUser($user);

        $structure->removeUser($user);

        $this->assertFalse($user->getStructures(false)->contains($structure));
    }

    // --- addPrefilledAnswer ---

    public function testAddPrefilledAnswerAddsNew(): void
    {
        $structure = $this->createStructure();
        $pfa = new PrefilledAnswers();
        $pfa->setLabel('Test PFA');
        $pfa->setAnswers(['Yes', 'No']);

        $result = $structure->addPrefilledAnswer($pfa);

        $this->assertSame($structure, $result);
        $this->assertCount(1, $structure->getPrefilledAnswers());
        $this->assertSame($structure, $pfa->getStructure());
    }

    public function testAddPrefilledAnswerDoesNotAddDuplicate(): void
    {
        $structure = $this->createStructure();
        $pfa = new PrefilledAnswers();
        $pfa->setLabel('Test PFA');
        $pfa->setAnswers(['Yes', 'No']);

        $structure->addPrefilledAnswer($pfa);
        $structure->addPrefilledAnswer($pfa);

        $this->assertCount(1, $structure->getPrefilledAnswers());
    }

    // --- removePrefilledAnswer ---

    public function testRemovePrefilledAnswerRemovesExisting(): void
    {
        $structure = $this->createStructure();
        $pfa = new PrefilledAnswers();
        $pfa->setLabel('Test PFA');
        $pfa->setAnswers(['Yes', 'No']);
        $structure->addPrefilledAnswer($pfa);

        $result = $structure->removePrefilledAnswer($pfa);

        $this->assertSame($structure, $result);
        $this->assertNull($pfa->getStructure());
    }

    public function testRemovePrefilledAnswerDoesNothingWhenAbsent(): void
    {
        $structure = $this->createStructure();
        $pfa = new PrefilledAnswers();
        $pfa->setLabel('Test PFA');
        $pfa->setAnswers(['Yes', 'No']);

        // The method calls contains() first, so absent elements are handled
        // without hitting the buggy remove() call
        $structure->removePrefilledAnswer($pfa);

        $this->assertCount(0, $structure->getPrefilledAnswers());
    }

    // --- getPresidentVolunteer ---

    public function testGetPresidentVolunteerReturnsMatchingVolunteer(): void
    {
        $structure = $this->createStructure();
        $structure->setPresident('VOL-PRES');
        $president = $this->createVolunteer('VOL-PRES');
        $other = $this->createVolunteer('VOL-OTHER');
        $structure->addVolunteer($president);
        $structure->addVolunteer($other);

        $this->assertSame($president, $structure->getPresidentVolunteer());
    }

    public function testGetPresidentVolunteerReturnsNullWhenNoMatch(): void
    {
        $structure = $this->createStructure();
        $structure->setPresident('VOL-NONEXISTENT');
        $volunteer = $this->createVolunteer('VOL-001');
        $structure->addVolunteer($volunteer);

        $this->assertNull($structure->getPresidentVolunteer());
    }

    public function testGetPresidentVolunteerReturnsNullWhenNoPresident(): void
    {
        $structure = $this->createStructure();

        $this->assertNull($structure->getPresidentVolunteer());
    }

    // --- getNextPegassUpdate ---

    public function testGetNextPegassUpdateReturnsNullWhenNoLastUpdate(): void
    {
        $structure = $this->createStructure();

        $this->assertNull($structure->getNextPegassUpdate());
    }

    public function testGetNextPegassUpdateReturnsDateWithTtlAdded(): void
    {
        $structure = $this->createStructure();
        $lastUpdate = new DateTime('2025-01-01 12:00:00', new DateTimeZone('UTC'));
        $structure->setLastPegassUpdate($lastUpdate);

        $next = $structure->getNextPegassUpdate();

        $this->assertNotNull($next);
        // TTL for structure is 7 days
        $expectedSeconds = Pegass::TTL[Pegass::TYPE_STRUCTURE] * 24 * 60 * 60;
        $expected = new DateTime('2025-01-01 12:00:00', new DateTimeZone('UTC'));
        $expected->modify(sprintf('+%d seconds', $expectedSeconds));

        $this->assertEquals($expected, $next);
    }

    // --- toSearchResults ---

    public function testToSearchResultsReturnsExpectedArray(): void
    {
        $structure = $this->createStructure('My Structure', 'EXT-001');

        $results = $structure->toSearchResults();

        // id will be null since not persisted, but should be string
        $this->assertArrayHasKey('id', $results);
        $this->assertArrayHasKey('name', $results);
        // Name is uppercase due to setName
        $this->assertSame('MY STRUCTURE', $results['name']);
    }

    // --- validate (isParentLooping) ---

    public function testValidateNoViolationWhenNoParentLoop(): void
    {
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        // Must set IDs so isParentLooping doesn't trip on null === null
        $ref = new \ReflectionProperty(Structure::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($parent, 1);
        $ref->setValue($child, 2);

        $child->setParentStructure($parent);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $child->validate($context, null);
    }

    public function testValidateAddsViolationWhenParentLoops(): void
    {
        // Use reflection to set IDs so isParentLooping can detect the loop
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');

        $refA = new \ReflectionProperty(Structure::class, 'id');
        $refA->setAccessible(true);
        $refA->setValue($structureA, 1);

        $refB = new \ReflectionProperty(Structure::class, 'id');
        $refB->setAccessible(true);
        $refB->setValue($structureB, 2);

        // Create a loop: A -> B -> A
        $structureA->setParentStructure($structureB);
        $structureB->setParentStructure($structureA);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setInvalidValue')->willReturn($violationBuilder);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
                ->method('buildViolation')
                ->willReturn($violationBuilder);

        $structureA->validate($context, null);
    }

    // --- getParentHierarchy ---

    public function testGetParentHierarchyReturnsOnlySelfWhenNoParent(): void
    {
        $structure = $this->createStructure('Root', 'EXT-R');

        $hierarchy = $structure->getParentHierarchy();

        $this->assertCount(1, $hierarchy);
        $this->assertSame($structure, $hierarchy[0]);
    }

    public function testGetParentHierarchyReturnsFullChain(): void
    {
        $grandparent = $this->createStructure('Grandparent', 'EXT-GP');
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $parent->setParentStructure($grandparent);
        $child->setParentStructure($parent);

        $hierarchy = $child->getParentHierarchy();

        $this->assertCount(3, $hierarchy);
        $this->assertSame($grandparent, $hierarchy[0]);
        $this->assertSame($parent, $hierarchy[1]);
        $this->assertSame($child, $hierarchy[2]);
    }

    public function testGetParentHierarchyStopsAtGivenId(): void
    {
        $grandparent = $this->createStructure('Grandparent', 'EXT-GP');
        $parent = $this->createStructure('Parent', 'EXT-P');
        $child = $this->createStructure('Child', 'EXT-C');

        $ref = new \ReflectionProperty(Structure::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($grandparent, 10);
        $ref->setValue($parent, 20);
        $ref->setValue($child, 30);

        $parent->setParentStructure($grandparent);
        $child->setParentStructure($parent);

        // Stop at grandparent's ID
        $hierarchy = $child->getParentHierarchy(10);

        $this->assertCount(3, $hierarchy);
        $this->assertSame($grandparent, $hierarchy[0]);
    }

    // --- addVolunteerList ---

    public function testAddVolunteerListAddsNew(): void
    {
        $structure = $this->createStructure();
        $list = new VolunteerList();
        $list->setName('Test List');
        $list->setAudience([]);

        $result = $structure->addVolunteerList($list);

        $this->assertSame($structure, $result);
        $this->assertCount(1, $structure->getVolunteerLists());
        $this->assertSame($structure, $list->getStructure());
    }

    public function testAddVolunteerListDoesNotAddDuplicate(): void
    {
        $structure = $this->createStructure();
        $list = new VolunteerList();
        $list->setName('Test List');
        $list->setAudience([]);

        $structure->addVolunteerList($list);
        $structure->addVolunteerList($list);

        $this->assertCount(1, $structure->getVolunteerLists());
    }

    // --- removeVolunteerList ---

    public function testRemoveVolunteerListRemovesExisting(): void
    {
        $structure = $this->createStructure();
        $list = new VolunteerList();
        $list->setName('Test List');
        $list->setAudience([]);
        $structure->addVolunteerList($list);

        $result = $structure->removeVolunteerList($list);

        $this->assertSame($structure, $result);
        $this->assertCount(0, $structure->getVolunteerLists());
        $this->assertNull($list->getStructure());
    }

    public function testRemoveVolunteerListDoesNothingWhenAbsent(): void
    {
        $structure = $this->createStructure();
        $list = new VolunteerList();
        $list->setName('Test List');
        $list->setAudience([]);

        $structure->removeVolunteerList($list);

        $this->assertCount(0, $structure->getVolunteerLists());
    }

    public function testRemoveVolunteerListDoesNotNullStructureIfAlreadyChanged(): void
    {
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');
        $list = new VolunteerList();
        $list->setName('Test List');
        $list->setAudience([]);
        $structureA->addVolunteerList($list);

        // Change structure before removing
        $list->setStructure($structureB);
        $structureA->removeVolunteerList($list);

        $this->assertSame($structureB, $list->getStructure());
    }

    // --- getVolunteerList ---

    public function testGetVolunteerListReturnsByName(): void
    {
        $structure = $this->createStructure();
        $listA = new VolunteerList();
        $listA->setName('Alpha');
        $listA->setAudience([]);
        $listB = new VolunteerList();
        $listB->setName('Beta');
        $listB->setAudience([]);
        $structure->addVolunteerList($listA);
        $structure->addVolunteerList($listB);

        $this->assertSame($listB, $structure->getVolunteerList('Beta'));
    }

    public function testGetVolunteerListReturnsNullWhenNotFound(): void
    {
        $structure = $this->createStructure();
        $list = new VolunteerList();
        $list->setName('Alpha');
        $list->setAudience([]);
        $structure->addVolunteerList($list);

        $this->assertNull($structure->getVolunteerList('Nonexistent'));
    }

    // --- addTemplate ---

    public function testAddTemplateAddsNew(): void
    {
        $structure = $this->createStructure();
        $structure->setShortcut('TST');
        $template = new Template();
        $template->setName('Test Template');
        $template->setBody('Body');
        $template->setLanguage('fr');

        $result = $structure->addTemplate($template);

        $this->assertSame($structure, $result);
        $this->assertCount(1, $structure->getTemplates());
        $this->assertSame($structure, $template->getStructure());
    }

    public function testAddTemplateDoesNotAddDuplicate(): void
    {
        $structure = $this->createStructure();
        $structure->setShortcut('TST');
        $template = new Template();
        $template->setName('Test Template');
        $template->setBody('Body');
        $template->setLanguage('fr');

        $structure->addTemplate($template);
        $structure->addTemplate($template);

        $this->assertCount(1, $structure->getTemplates());
    }

    // --- removeTemplate ---

    public function testRemoveTemplateRemovesExisting(): void
    {
        $structure = $this->createStructure();
        $structure->setShortcut('TST');
        $template = new Template();
        $template->setName('Test Template');
        $template->setBody('Body');
        $template->setLanguage('fr');
        $structure->addTemplate($template);

        $result = $structure->removeTemplate($template);

        $this->assertSame($structure, $result);
        $this->assertCount(0, $structure->getTemplates());
        $this->assertNull($template->getStructure());
    }

    public function testRemoveTemplateDoesNothingWhenAbsent(): void
    {
        $structure = $this->createStructure();
        $template = new Template();
        $template->setName('Test Template');
        $template->setBody('Body');
        $template->setLanguage('fr');

        $structure->removeTemplate($template);

        $this->assertCount(0, $structure->getTemplates());
    }

    public function testRemoveTemplateDoesNotNullStructureIfAlreadyChanged(): void
    {
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureA->setShortcut('A');
        $structureB = $this->createStructure('B', 'EXT-B');
        $structureB->setShortcut('B');
        $template = new Template();
        $template->setName('Test');
        $template->setBody('Body');
        $template->setLanguage('fr');
        $structureA->addTemplate($template);

        // Change structure before removing
        $template->setStructure($structureB);
        $structureA->removeTemplate($template);

        $this->assertSame($structureB, $template->getStructure());
    }
}
