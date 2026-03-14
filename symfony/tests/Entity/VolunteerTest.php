<?php

namespace App\Tests\Entity;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Pegass;
use App\Entity\Phone;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Entity\VolunteerList;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class VolunteerTest extends TestCase
{
    private function createVolunteer(
        string $externalId = 'VOL-001',
        string $firstName = 'Jean-Pierre',
        string $lastName = 'DUPONT',
        bool $enabled = true
    ): Volunteer {
        $volunteer = new Volunteer();
        $volunteer->setExternalId($externalId);
        $volunteer->setFirstName($firstName);
        $volunteer->setLastName($lastName);
        $volunteer->setEnabled($enabled);

        return $volunteer;
    }

    private function createPhone(string $e164 = '+33612345678', bool $preferred = false): Phone
    {
        $phone = new Phone();
        $phone->setE164($e164);
        $phone->setNational('06 12 34 56 78');
        $phone->setInternational('+33 6 12 34 56 78');
        $phone->setCountryCode('FR');
        $phone->setPrefix(33);
        $phone->setPreferred($preferred);

        return $phone;
    }

    private function createStructure(string $name = 'Test Structure', string $externalId = 'EXT-001', bool $enabled = true): Structure
    {
        $structure = new Structure();
        $structure->setName($name);
        $structure->setExternalId($externalId);
        $structure->setEnabled($enabled);

        return $structure;
    }

    private function createBadge(string $name = 'Badge', string $externalId = 'BADGE-001', bool $enabled = true, bool $visible = false): Badge
    {
        $badge = new Badge();
        $badge->setName($name);
        $badge->setExternalId($externalId);
        $badge->setEnabled($enabled);
        $badge->setVisibility($visible);

        return $badge;
    }

    private function createUser(string $username = 'user@example.com', bool $trusted = true): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword('password');
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');
        $user->setIsTrusted($trusted);

        return $user;
    }

    // --- getPhone ---

    public function testGetPhoneReturnsPreferredPhone(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', false);
        $phoneB = $this->createPhone('+33622222222', true);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $result = $volunteer->getPhone();

        $this->assertSame($phoneB, $result);
    }

    public function testGetPhoneReturnsNullWhenNoPhones(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getPhone());
    }

    public function testGetPhoneReturnsNullWhenNoPreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33611111111', false);
        $volunteer->addPhone($phone);

        $this->assertNull($volunteer->getPhone());
    }

    // --- getPhoneByNumber ---

    public function testGetPhoneByNumberReturnsMatching(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');
        $volunteer->addPhone($phone);

        $this->assertSame($phone, $volunteer->getPhoneByNumber('+33612345678'));
    }

    public function testGetPhoneByNumberReturnsNullWhenNotFound(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');
        $volunteer->addPhone($phone);

        $this->assertNull($volunteer->getPhoneByNumber('+33699999999'));
    }

    // --- hasPhoneNumber ---

    public function testHasPhoneNumberReturnsTrueWhenPresent(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');
        $volunteer->addPhone($phone);

        $this->assertTrue($volunteer->hasPhoneNumber('+33612345678'));
    }

    public function testHasPhoneNumberReturnsFalseWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');
        $volunteer->addPhone($phone);

        $this->assertFalse($volunteer->hasPhoneNumber('+33699999999'));
    }

    public function testHasPhoneNumberReturnsFalseWhenNoPhones(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertFalse($volunteer->hasPhoneNumber('+33612345678'));
    }

    // --- getDisplayName ---

    public function testGetDisplayNameWithFirstAndLastName(): void
    {
        $volunteer = $this->createVolunteer('VOL-001', 'jean-pierre', 'DUPONT');

        $displayName = $volunteer->getDisplayName();

        // toName capitalizes first letter of each word, lowercases rest
        $this->assertSame('Jean-Pierre Dupont', $displayName);
    }

    public function testGetDisplayNameWithoutNames(): void
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('vol-123');

        $displayName = $volunteer->getDisplayName();

        $this->assertSame('#VOL-123', $displayName);
    }

    public function testGetDisplayNameWithOnlyFirstName(): void
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('vol-123');
        $volunteer->setFirstName('Jean');

        // lastName is null, so it falls back to externalId format
        $displayName = $volunteer->getDisplayName();
        $this->assertSame('#VOL-123', $displayName);
    }

    // --- isCallable ---

    public function testIsCallableWithPhoneAndEnabled(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setPhoneNumberOptin(true);
        $phone = $this->createPhone('+33612345678', true);
        $volunteer->addPhone($phone);

        $this->assertTrue($volunteer->isCallable());
    }

    public function testIsCallableWithEmailAndEnabled(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEmail('test@example.com');
        $volunteer->setEmailOptin(true);

        $this->assertTrue($volunteer->isCallable());
    }

    public function testIsCallableReturnsFalseWhenDisabled(): void
    {
        $volunteer = $this->createVolunteer('VOL-001', 'John', 'Doe', false);
        $volunteer->setEmail('test@example.com');
        $volunteer->setEmailOptin(true);

        $this->assertFalse($volunteer->isCallable());
    }

    public function testIsCallableReturnsFalseWhenOptedOut(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEmail('test@example.com');
        $volunteer->setEmailOptin(true);
        // Set optout far in the future
        $volunteer->setOptoutUntil(new DateTime('+1 year'));

        $this->assertFalse($volunteer->isCallable());
    }

    public function testIsCallableReturnsTrueWhenOptoutExpired(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEmail('test@example.com');
        $volunteer->setEmailOptin(true);
        // Set optout in the past
        $volunteer->setOptoutUntil(new DateTime('-1 day'));

        $this->assertTrue($volunteer->isCallable());
    }

    public function testIsCallableReturnsFalseWithNoContactInfo(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertFalse($volunteer->isCallable());
    }

    public function testIsCallableReturnsFalseWhenPhoneOptinFalse(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setPhoneNumberOptin(false);
        $phone = $this->createPhone('+33612345678', true);
        $volunteer->addPhone($phone);

        // No email and phone optin is false
        $this->assertFalse($volunteer->isCallable());
    }

    // --- getStructureIds ---

    public function testGetStructureIdsReturnsIds(): void
    {
        $volunteer = $this->createVolunteer();
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');

        $ref = new \ReflectionProperty(Structure::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($structureA, 10);
        $ref->setValue($structureB, 20);

        $volunteer->addStructure($structureA);
        $volunteer->addStructure($structureB);

        $ids = $volunteer->getStructureIds();

        $this->assertContains(10, $ids);
        $this->assertContains(20, $ids);
        $this->assertCount(2, $ids);
    }

    // --- getStructures ---

    public function testGetStructuresReturnsOnlyEnabledByDefault(): void
    {
        $volunteer = $this->createVolunteer();
        $enabled = $this->createStructure('Enabled', 'EXT-E', true);
        $disabled = $this->createStructure('Disabled', 'EXT-D', false);
        $volunteer->addStructure($enabled);
        $volunteer->addStructure($disabled);

        $structures = $volunteer->getStructures();

        $this->assertCount(1, $structures);
        $this->assertTrue($structures->contains($enabled));
    }

    public function testGetStructuresReturnsAllWhenFlagIsFalse(): void
    {
        $volunteer = $this->createVolunteer();
        $enabled = $this->createStructure('Enabled', 'EXT-E', true);
        $disabled = $this->createStructure('Disabled', 'EXT-D', false);
        $volunteer->addStructure($enabled);
        $volunteer->addStructure($disabled);

        $structures = $volunteer->getStructures(false);

        $this->assertCount(2, $structures);
    }

    // --- addStructure ---

    public function testAddStructureAddsNew(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure();

        $result = $volunteer->addStructure($structure);

        $this->assertSame($volunteer, $result);
        $this->assertTrue($volunteer->getStructures(false)->contains($structure));
    }

    public function testAddStructureDoesNotAddDuplicate(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure();

        $volunteer->addStructure($structure);
        $volunteer->addStructure($structure);

        $this->assertCount(1, $volunteer->getStructures(false));
    }

    // --- removeStructure ---

    public function testRemoveStructureRemovesExisting(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure();
        $volunteer->addStructure($structure);

        $result = $volunteer->removeStructure($structure);

        $this->assertSame($volunteer, $result);
        $this->assertFalse($volunteer->getStructures(false)->contains($structure));
    }

    public function testRemoveStructureDoesNothingWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure();

        $volunteer->removeStructure($structure);

        $this->assertCount(0, $volunteer->getStructures(false));
    }

    // --- syncStructures ---

    public function testSyncStructuresAddsNewAndRemovesOld(): void
    {
        $volunteer = $this->createVolunteer();
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');
        $structureC = $this->createStructure('C', 'EXT-C');

        $volunteer->addStructure($structureA);
        $volunteer->addStructure($structureB);

        // Sync to B and C (should remove A, keep B, add C)
        $volunteer->syncStructures([$structureB, $structureC]);

        $structures = $volunteer->getStructures(false);
        $this->assertFalse($structures->contains($structureA));
        $this->assertTrue($structures->contains($structureB));
        $this->assertTrue($structures->contains($structureC));
    }

    public function testSyncStructuresWithEmptyArrayClearsAll(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure();
        $volunteer->addStructure($structure);

        $volunteer->syncStructures([]);

        $this->assertCount(0, $volunteer->getStructures(false));
    }

    // --- getMainStructure ---

    public function testGetMainStructureReturnsHighestInHierarchy(): void
    {
        $volunteer = $this->createVolunteer();
        $grandparent = $this->createStructure('GP', 'EXT-GP');
        $parent = $this->createStructure('P', 'EXT-P');
        $child = $this->createStructure('C', 'EXT-C');

        $parent->setParentStructure($grandparent);
        $child->setParentStructure($parent);

        $volunteer->addStructure($child);
        $volunteer->addStructure($parent);

        // parent has fewer ancestors (1) than child (2)
        $this->assertSame($parent, $volunteer->getMainStructure());
    }

    public function testGetMainStructureReturnsNullWhenEmpty(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getMainStructure());
    }

    public function testGetMainStructureReturnsAlphabeticallyFirstAtSameLevel(): void
    {
        $volunteer = $this->createVolunteer();
        $alpha = $this->createStructure('Alpha', 'EXT-A');
        $beta = $this->createStructure('Beta', 'EXT-B');

        $volunteer->addStructure($beta);
        $volunteer->addStructure($alpha);

        // Both at same level (0 ancestors), alpha comes first alphabetically
        $this->assertSame($alpha, $volunteer->getMainStructure());
    }

    // --- toSearchResults ---

    public function testToSearchResultsReturnsExpectedArray(): void
    {
        $volunteer = $this->createVolunteer('VOL-001', 'Jean', 'Dupont');

        $results = $volunteer->toSearchResults();

        $this->assertArrayHasKey('id', $results);
        $this->assertArrayHasKey('external-id', $results);
        $this->assertArrayHasKey('human', $results);
        $this->assertSame('VOL-001', $results['external-id']);
        $this->assertStringContainsString('Jean', $results['human']);
        $this->assertStringContainsString('Dupont', $results['human']);
    }

    // --- getVisibleBadges ---

    public function testGetVisibleBadgesReturnsOnlyVisible(): void
    {
        $volunteer = $this->createVolunteer();
        $visibleBadge = $this->createBadge('Visible', 'B-V', true, true);
        $invisibleBadge = $this->createBadge('Invisible', 'B-I', true, false);
        $volunteer->addBadge($visibleBadge);
        $volunteer->addBadge($invisibleBadge);

        $visible = $volunteer->getVisibleBadges();

        $this->assertCount(1, $visible);
        $this->assertSame($visibleBadge, $visible[0]);
    }

    public function testGetVisibleBadgesFiltersByUserFavorites(): void
    {
        $volunteer = $this->createVolunteer();
        $badgeA = $this->createBadge('A', 'B-A', true, true);
        $badgeB = $this->createBadge('B', 'B-B', true, true);
        $volunteer->addBadge($badgeA);
        $volunteer->addBadge($badgeB);

        $user = $this->createUser();
        $user->addFavoriteBadge($badgeA);

        $visible = $volunteer->getVisibleBadges($user);

        $this->assertCount(1, $visible);
        $this->assertSame($badgeA, $visible[0]);
    }

    // --- getBadgesFilteredFromSynonyms ---

    public function testGetBadgesFilteredFromSynonymsReplacesSynonyms(): void
    {
        $volunteer = $this->createVolunteer();
        $original = $this->createBadge('Original', 'B-O');
        $synonym = $this->createBadge('Synonym', 'B-S');

        // synonym points to original
        $synonym->setSynonym($original);

        $volunteer->addBadge($synonym);

        $badges = $volunteer->getBadgesFilteredFromSynonyms();

        // The synonym badge should be replaced by the original
        $this->assertContains($original, $badges);
        $this->assertNotContains($synonym, $badges);
    }

    public function testGetBadgesFilteredFromSynonymsKeepsNonSynonyms(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Normal', 'B-N');
        $volunteer->addBadge($badge);

        $badges = $volunteer->getBadgesFilteredFromSynonyms();

        $this->assertContains($badge, $badges);
    }

    public function testGetBadgesFilteredFromSynonymsDoesNotReplaceSynonymIfOriginalAlreadyPresent(): void
    {
        $volunteer = $this->createVolunteer();
        $original = $this->createBadge('Original', 'B-O');
        $synonym = $this->createBadge('Synonym', 'B-S');
        $synonym->setSynonym($original);

        $volunteer->addBadge($original);
        $volunteer->addBadge($synonym);

        $badges = $volunteer->getBadgesFilteredFromSynonyms();

        // Original present, so synonym stays (because in_array($original, $badges) is true)
        $this->assertContains($original, $badges);
        $this->assertContains($synonym, $badges);
    }

    // --- getBadges ---

    public function testGetBadgesReturnsOnlyEnabledByDefault(): void
    {
        $volunteer = $this->createVolunteer();
        $enabled = $this->createBadge('Enabled', 'B-E', true);
        $disabled = $this->createBadge('Disabled', 'B-D', false);
        $volunteer->addBadge($enabled);
        $volunteer->addBadge($disabled);

        $badges = $volunteer->getBadges();

        $this->assertCount(1, $badges);
        $this->assertTrue($badges->contains($enabled));
    }

    public function testGetBadgesReturnsAllWhenFlagIsFalse(): void
    {
        $volunteer = $this->createVolunteer();
        $enabled = $this->createBadge('Enabled', 'B-E', true);
        $disabled = $this->createBadge('Disabled', 'B-D', false);
        $volunteer->addBadge($enabled);
        $volunteer->addBadge($disabled);

        $badges = $volunteer->getBadges(false);

        $this->assertCount(2, $badges);
    }

    // --- setBadges ---

    public function testSetBadgesReplacesAllBadges(): void
    {
        $volunteer = $this->createVolunteer();
        $badgeA = $this->createBadge('A', 'B-A');
        $badgeB = $this->createBadge('B', 'B-B');
        $badgeC = $this->createBadge('C', 'B-C');
        $volunteer->addBadge($badgeA);

        $volunteer->setBadges([$badgeB, $badgeC]);

        $badges = $volunteer->getBadges(false);
        $this->assertCount(2, $badges);
        $this->assertTrue($badges->contains($badgeB));
        $this->assertTrue($badges->contains($badgeC));
        $this->assertFalse($badges->contains($badgeA));
    }

    // --- getNextPegassUpdate ---

    public function testGetNextPegassUpdateReturnsNullWhenNoLastUpdate(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getNextPegassUpdate());
    }

    public function testGetNextPegassUpdateReturnsDateWithTtlAdded(): void
    {
        $volunteer = $this->createVolunteer();
        $lastUpdate = new DateTime('2025-01-01 12:00:00', new DateTimeZone('UTC'));
        $volunteer->setLastPegassUpdate($lastUpdate);

        $next = $volunteer->getNextPegassUpdate();

        $this->assertNotNull($next);
        $expectedSeconds = Pegass::TTL[Pegass::TYPE_VOLUNTEER] * 24 * 60 * 60;
        $expected = new DateTime('2025-01-01 12:00:00', new DateTimeZone('UTC'));
        $expected->modify(sprintf('+%d seconds', $expectedSeconds));

        $this->assertEquals($expected, $next);
    }

    // --- getTruncatedName ---

    public function testGetTruncatedNameWithNames(): void
    {
        $volunteer = $this->createVolunteer('VOL-001', 'jean-pierre', 'DUPONT');

        $truncated = $volunteer->getTruncatedName();

        // First name capitalized + first letter of last name uppercased
        $this->assertSame('Jean-Pierre D', $truncated);
    }

    public function testGetTruncatedNameWithoutNames(): void
    {
        $volunteer = new Volunteer();
        $volunteer->setExternalId('vol-123');

        $this->assertSame('#VOL-123', $volunteer->getTruncatedName());
    }

    // --- toName (tested indirectly via getDisplayName) ---

    public function testToNameCapitalizesMultiWordHyphenated(): void
    {
        $volunteer = $this->createVolunteer('VOL-001', 'JEAN-PIERRE', 'DE LA FONTAINE');

        $displayName = $volunteer->getDisplayName();

        $this->assertSame('Jean-Pierre De La Fontaine', $displayName);
    }

    // --- getUser ---

    public function testGetUserReturnsUserWhenTrusted(): void
    {
        $volunteer = $this->createVolunteer();
        $user = $this->createUser('user@test.com', true);
        $volunteer->setUser($user);

        $this->assertSame($user, $volunteer->getUser());
    }

    public function testGetUserReturnsNullWhenNotTrusted(): void
    {
        $volunteer = $this->createVolunteer();
        $user = $this->createUser('user@test.com', false);
        $volunteer->setUser($user);

        $this->assertNull($volunteer->getUser());
    }

    public function testGetUserReturnsNullWhenNoUser(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getUser());
    }

    // --- shouldBeLocked ---

    public function testShouldBeLockedReturnsTrueWhenPropertiesDiffer(): void
    {
        $old = $this->createVolunteer('VOL-001', 'John', 'Doe');
        $new = $this->createVolunteer('VOL-001', 'Jane', 'Doe');

        $this->assertTrue($new->shouldBeLocked($old));
    }

    public function testShouldBeLockedReturnsFalseWhenPropertiesSame(): void
    {
        $old = $this->createVolunteer('VOL-001', 'John', 'Doe');
        $new = $this->createVolunteer('VOL-001', 'John', 'Doe');

        $this->assertFalse($new->shouldBeLocked($old));
    }

    public function testShouldBeLockedIgnoresSkippedFields(): void
    {
        $old = $this->createVolunteer('VOL-001', 'John', 'Doe');
        $new = $this->createVolunteer('VOL-001', 'John', 'Doe');

        // Changing email should not affect shouldBeLocked because 'email' is in the skip list
        $old->setEmail('old@test.com');
        $new->setEmail('new@test.com');

        $this->assertFalse($new->shouldBeLocked($old));
    }

    // --- getHiddenPhone ---

    public function testGetHiddenPhoneReturnsHiddenFormat(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678', true);
        $phone->setNational('06 12 34 56 78');
        $volunteer->addPhone($phone);

        $hidden = $volunteer->getHiddenPhone();

        $this->assertNotNull($hidden);
        // getHidden: first 4 chars + stars + last 4 chars
        $this->assertStringStartsWith('06 1', $hidden);
        $this->assertStringContainsString('*', $hidden);
        $this->assertStringEndsWith('6 78', $hidden);
    }

    public function testGetHiddenPhoneReturnsNullWhenNoPhone(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getHiddenPhone());
    }

    // --- getHiddenEmail ---

    public function testGetHiddenEmailReturnsHiddenFormat(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEmail('john.doe@example.com');

        $hidden = $volunteer->getHiddenEmail();

        $this->assertNotNull($hidden);
        $this->assertStringStartsWith('j', $hidden);
        $this->assertStringContainsString('*', $hidden);
        $this->assertStringEndsWith('@example.com', $hidden);
    }

    public function testGetHiddenEmailReturnsNullWhenNoEmail(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertNull($volunteer->getHiddenEmail());
    }

    public function testGetHiddenEmailShortUsername(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEmail('ab@test.com');

        $hidden = $volunteer->getHiddenEmail();

        // username "ab": first char + stars (max(0, 0)) + last char
        $this->assertSame('ab@test.com', $hidden);
    }

    // --- doNotDisableRedCallUsers ---

    public function testDoNotDisableRedCallUsersAddsViolationWhenUserExistsAndDisabled(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEnabled(false);
        $user = $this->createUser();
        $volunteer->setUser($user);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
                ->method('buildViolation')
                ->with('form.volunteer.errors.redcall_user')
                ->willReturn($violationBuilder);

        $volunteer->doNotDisableRedCallUsers($context, null);
    }

    public function testDoNotDisableRedCallUsersNoViolationWhenNoUser(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEnabled(false);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $volunteer->doNotDisableRedCallUsers($context, null);
    }

    public function testDoNotDisableRedCallUsersNoViolationWhenEnabled(): void
    {
        $volunteer = $this->createVolunteer();
        $volunteer->setEnabled(true);
        $user = $this->createUser();
        $volunteer->setUser($user);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $volunteer->doNotDisableRedCallUsers($context, null);
    }

    // --- removeBadge ---

    public function testRemoveBadgeRemovesExisting(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Test', 'B-T');
        $volunteer->addBadge($badge);
        // Simulate the inverse side
        $badge->addVolunteer($volunteer);

        $result = $volunteer->removeBadge($badge);

        $this->assertSame($volunteer, $result);
        $this->assertFalse($volunteer->getBadges(false)->contains($badge));
    }

    public function testRemoveBadgeDoesNothingWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Test', 'B-T');

        $volunteer->removeBadge($badge);

        $this->assertCount(0, $volunteer->getBadges(false));
    }

    // --- addList ---

    public function testAddListAddsNew(): void
    {
        $volunteer = $this->createVolunteer();
        $list = new VolunteerList();
        $list->setName('Test');
        $list->setAudience([]);

        $result = $volunteer->addList($list);

        $this->assertSame($volunteer, $result);
        $this->assertCount(1, $volunteer->getLists());
    }

    public function testAddListDoesNotAddDuplicate(): void
    {
        $volunteer = $this->createVolunteer();
        $list = new VolunteerList();
        $list->setName('Test');
        $list->setAudience([]);

        $volunteer->addList($list);
        $volunteer->addList($list);

        $this->assertCount(1, $volunteer->getLists());
    }

    // --- removeList ---

    public function testRemoveListRemovesExisting(): void
    {
        $volunteer = $this->createVolunteer();
        $list = new VolunteerList();
        $list->setName('Test');
        $list->setAudience([]);
        $list->addVolunteer($volunteer);
        $volunteer->addList($list);

        $result = $volunteer->removeList($list);

        $this->assertSame($volunteer, $result);
        $this->assertCount(0, $volunteer->getLists());
    }

    public function testRemoveListDoesNothingWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();
        $list = new VolunteerList();
        $list->setName('Test');
        $list->setAudience([]);

        $volunteer->removeList($list);

        $this->assertCount(0, $volunteer->getLists());
    }

    // --- removeExpiredBadges ---

    public function testRemoveExpiredBadgesRemovesExpired(): void
    {
        $volunteer = $this->createVolunteer();
        $current = $this->createBadge('Current', 'B-C');
        $expired = $this->createBadge('Expired', 'B-E');
        $expired->setExpiresAt(new DateTimeImmutable('-1 day'));

        $volunteer->addBadge($current);
        $volunteer->addBadge($expired);

        $volunteer->removeExpiredBadges();

        $badges = $volunteer->getBadges(false);
        $this->assertTrue($badges->contains($current));
        $this->assertFalse($badges->contains($expired));
    }

    public function testRemoveExpiredBadgesKeepsNonExpired(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Future', 'B-F');
        $badge->setExpiresAt(new DateTimeImmutable('+1 year'));
        $volunteer->addBadge($badge);

        $volunteer->removeExpiredBadges();

        $this->assertCount(1, $volunteer->getBadges(false));
    }

    // --- hasBadge ---

    public function testHasBadgeReturnsTrueWhenPresent(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('TestBadge', 'B-T');
        $volunteer->addBadge($badge);

        $this->assertTrue($volunteer->hasBadge('TestBadge'));
    }

    public function testHasBadgeReturnsFalseWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertFalse($volunteer->hasBadge('NonexistentBadge'));
    }

    // --- setExternalBadges ---

    public function testSetExternalBadgesReplacesExternalAndKeepsInternal(): void
    {
        $volunteer = $this->createVolunteer();
        $externalBadge = $this->createBadge('External', 'B-EXT');
        $internalBadge = new Badge();
        $internalBadge->setName('Internal');
        // No externalId set, but Badge constructor doesn't set it either
        // Actually Badge always has externalId via setExternalId. Let's use reflection.
        $ref = new \ReflectionProperty(Badge::class, 'externalId');
        $ref->setAccessible(true);
        $ref->setValue($internalBadge, null);

        $volunteer->addBadge($externalBadge);
        $volunteer->addBadge($internalBadge);

        $newExternal = $this->createBadge('NewExternal', 'B-NEW');
        $volunteer->setExternalBadges([$newExternal]);

        $badges = $volunteer->getBadges(false);
        $this->assertTrue($badges->contains($internalBadge), 'Internal badge should be kept');
        $this->assertTrue($badges->contains($newExternal), 'New external badge should be added');
        $this->assertFalse($badges->contains($externalBadge), 'Old external badge should be removed');
    }

    // --- addBadge ---

    public function testAddBadgeAddsNew(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Test', 'B-T');

        $result = $volunteer->addBadge($badge);

        $this->assertSame($volunteer, $result);
        $this->assertTrue($volunteer->getBadges(false)->contains($badge));
    }

    public function testAddBadgeDoesNotAddDuplicate(): void
    {
        $volunteer = $this->createVolunteer();
        $badge = $this->createBadge('Test', 'B-T');

        $volunteer->addBadge($badge);
        $volunteer->addBadge($badge);

        $this->assertCount(1, $volunteer->getBadges(false));
    }

    // --- validate ---

    public function testValidateNoViolationWhenNoPhones(): void
    {
        $volunteer = $this->createVolunteer();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $volunteer->validate($context, null);
    }

    public function testValidateAutoSetsPreferredWhenSinglePhoneWithoutPreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678', false);
        $volunteer->addPhone($phone);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $volunteer->validate($context, null);

        $this->assertTrue($phone->isPreferred());
    }

    public function testValidateAddsViolationWhenMultiplePhonesAndNoPreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', false);
        $phoneB = $this->createPhone('+33622222222', false);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->expects($this->atLeastOnce())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->atLeastOnce())
                ->method('buildViolation')
                ->willReturn($violationBuilder);

        $volunteer->validate($context, null);
    }

    public function testValidateAddsViolationWhenMultiplePreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', true);
        $phoneB = $this->createPhone('+33622222222', true);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
                ->method('buildViolation')
                ->with('form.phone_card.error_multi_preferred')
                ->willReturn($violationBuilder);

        $volunteer->validate($context, null);
    }

    public function testValidateAddsViolationForDuplicatePhones(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33612345678', true);
        $phoneB = $this->createPhone('+33612345678', false);
        $volunteer->addPhone($phoneA);
        // Force add duplicate by using reflection since addPhone prevents duplicates by object identity
        $ref = new \ReflectionProperty(Volunteer::class, 'phones');
        $ref->setAccessible(true);
        $phones = $ref->getValue($volunteer);
        $phones->add($phoneB);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturn($violationBuilder);
        $violationBuilder->expects($this->atLeastOnce())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('buildViolation')
                ->willReturn($violationBuilder);

        $volunteer->validate($context, null);
    }

    // --- getBadgePriority ---

    public function testGetBadgePriorityReturnsLowest(): void
    {
        $volunteer = $this->createVolunteer();
        $badgeA = $this->createBadge('A', 'B-A', true, true);
        $badgeA->setRenderingPriority(100);
        $badgeB = $this->createBadge('B', 'B-B', true, true);
        $badgeB->setRenderingPriority(50);
        $volunteer->addBadge($badgeA);
        $volunteer->addBadge($badgeB);

        $this->assertSame(50, $volunteer->getBadgePriority());
    }

    public function testGetBadgePriorityReturnsMaxWhenNoBadges(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertSame(0xFFFFFFFF, $volunteer->getBadgePriority());
    }

    // --- addPhone ---

    public function testAddPhoneAddsNew(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');

        $result = $volunteer->addPhone($phone);

        $this->assertSame($volunteer, $result);
        $this->assertCount(1, $volunteer->getPhones());
        $this->assertTrue($phone->getVolunteers()->contains($volunteer));
    }

    public function testAddPhoneDoesNotAddDuplicate(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');

        $volunteer->addPhone($phone);
        $volunteer->addPhone($phone);

        $this->assertCount(1, $volunteer->getPhones());
    }

    // --- setPhoneAsPreferred ---

    public function testSetPhoneAsPreferredSetsCorrectOne(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', true);
        $phoneB = $this->createPhone('+33622222222', false);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $volunteer->setPhoneAsPreferred($phoneB);

        $this->assertFalse($phoneA->isPreferred());
        $this->assertTrue($phoneB->isPreferred());
    }

    public function testSetPhoneAsPreferredDoesNothingWhenPhoneNotInCollection(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', true);
        $phoneB = $this->createPhone('+33622222222', false);
        $volunteer->addPhone($phoneA);

        $volunteer->setPhoneAsPreferred($phoneB);

        $this->assertTrue($phoneA->isPreferred());
    }

    // --- removePhone ---

    public function testRemovePhoneRemovesExisting(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');
        $volunteer->addPhone($phone);

        $result = $volunteer->removePhone($phone);

        $this->assertSame($volunteer, $result);
        $this->assertCount(0, $volunteer->getPhones());
    }

    public function testRemovePhoneDoesNothingWhenAbsent(): void
    {
        $volunteer = $this->createVolunteer();
        $phone = $this->createPhone('+33612345678');

        $volunteer->removePhone($phone);

        $this->assertCount(0, $volunteer->getPhones());
    }

    // --- ensureOnePhoneIsPreferred ---

    public function testEnsureOnePhoneIsPreferredSetsFirstWhenNonePreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', false);
        $phoneB = $this->createPhone('+33622222222', false);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $volunteer->ensureOnePhoneIsPreferred();

        $this->assertTrue($phoneA->isPreferred());
    }

    public function testEnsureOnePhoneIsPreferredDoesNothingWhenOneAlreadyPreferred(): void
    {
        $volunteer = $this->createVolunteer();
        $phoneA = $this->createPhone('+33611111111', false);
        $phoneB = $this->createPhone('+33622222222', true);
        $volunteer->addPhone($phoneA);
        $volunteer->addPhone($phoneB);

        $volunteer->ensureOnePhoneIsPreferred();

        $this->assertFalse($phoneA->isPreferred());
        $this->assertTrue($phoneB->isPreferred());
    }

    public function testEnsureOnePhoneIsPreferredDoesNothingWhenNoPhones(): void
    {
        $volunteer = $this->createVolunteer();

        // Should not throw
        $volunteer->ensureOnePhoneIsPreferred();

        $this->assertCount(0, $volunteer->getPhones());
    }

    // --- needsShortcutInMessages ---

    public function testNeedsShortcutInMessagesReturnsTrueWhenMultipleStructures(): void
    {
        $volunteer = $this->createVolunteer();
        $structureA = $this->createStructure('A', 'EXT-A');
        $structureB = $this->createStructure('B', 'EXT-B');
        $volunteer->addStructure($structureA);
        $volunteer->addStructure($structureB);

        $this->assertTrue($volunteer->needsShortcutInMessages());
    }

    public function testNeedsShortcutInMessagesTrueWhenUserHasMultipleStructures(): void
    {
        $volunteer = $this->createVolunteer();
        $structureA = $this->createStructure('A', 'EXT-A');
        $volunteer->addStructure($structureA);

        $user = $this->createUser();
        $user->addStructure($structureA);
        $user->addStructure($this->createStructure('B', 'EXT-B'));
        $volunteer->setUser($user);

        $this->assertTrue($volunteer->needsShortcutInMessages());
    }

    public function testNeedsShortcutInMessagesReturnsFalseWhenSingleStructureAndNoUser(): void
    {
        $volunteer = $this->createVolunteer();
        $structure = $this->createStructure('A', 'EXT-A');
        $volunteer->addStructure($structure);

        $this->assertFalse($volunteer->needsShortcutInMessages());
    }

    public function testNeedsShortcutInMessagesReturnsFalseWhenNoStructuresAndNoUser(): void
    {
        $volunteer = $this->createVolunteer();

        $this->assertFalse($volunteer->needsShortcutInMessages());
    }
}
