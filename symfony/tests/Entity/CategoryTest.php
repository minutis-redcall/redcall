<?php

namespace App\Tests\Entity;

use App\Entity\Badge;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private function createCategory(string $name, ?int $id = null): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setExternalId('ext-' . ($id ?? random_int(1, 999999)));
        if (null !== $id) {
            $ref = new \ReflectionProperty(Category::class, 'id');
            $ref->setAccessible(true);
            $ref->setValue($category, $id);
        }

        return $category;
    }

    private function createBadge(string $name, bool $enabled = true): Badge
    {
        $badge = new Badge();
        $badge->setName($name);
        $badge->setExternalId('ext-badge-' . random_int(1, 999999));
        $badge->setEnabled($enabled);

        return $badge;
    }

    // --- getBadges ---

    public function testGetBadgesEmpty(): void
    {
        $category = $this->createCategory('Test');
        $this->assertCount(0, $category->getBadges());
    }

    public function testGetBadgesOnlyEnabledByDefault(): void
    {
        $category = $this->createCategory('Test');
        $enabledBadge = $this->createBadge('Enabled', true);
        $disabledBadge = $this->createBadge('Disabled', false);

        $category->addBadge($enabledBadge);
        $category->addBadge($disabledBadge);

        $result = $category->getBadges();
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($enabledBadge));
        $this->assertFalse($result->contains($disabledBadge));
    }

    public function testGetBadgesAllWhenFlagIsFalse(): void
    {
        $category = $this->createCategory('Test');
        $enabledBadge = $this->createBadge('Enabled', true);
        $disabledBadge = $this->createBadge('Disabled', false);

        $category->addBadge($enabledBadge);
        $category->addBadge($disabledBadge);

        $result = $category->getBadges(false);
        $this->assertCount(2, $result);
    }

    public function testGetBadgesAllEnabled(): void
    {
        $category = $this->createCategory('Test');
        $badge1 = $this->createBadge('Badge1', true);
        $badge2 = $this->createBadge('Badge2', true);

        $category->addBadge($badge1);
        $category->addBadge($badge2);

        $this->assertCount(2, $category->getBadges());
        $this->assertCount(2, $category->getBadges(false));
    }

    // --- addBadge ---

    public function testAddBadge(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');

        $result = $category->addBadge($badge);

        $this->assertSame($category, $result, 'addBadge should return $this for fluent interface');
        $this->assertCount(1, $category->getBadges(false));
        $this->assertSame($category, $badge->getCategory());
    }

    public function testAddBadgeDoesNotAddDuplicate(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');

        $category->addBadge($badge);
        $category->addBadge($badge);

        $this->assertCount(1, $category->getBadges(false));
    }

    public function testAddBadgeSetsCategory(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');

        $this->assertNull($badge->getCategory());

        $category->addBadge($badge);

        $this->assertSame($category, $badge->getCategory());
    }

    public function testAddMultipleBadges(): void
    {
        $category = $this->createCategory('Test');
        $badge1 = $this->createBadge('Badge1');
        $badge2 = $this->createBadge('Badge2');
        $badge3 = $this->createBadge('Badge3');

        $category->addBadge($badge1);
        $category->addBadge($badge2);
        $category->addBadge($badge3);

        $this->assertCount(3, $category->getBadges(false));
    }

    // --- removeBadge ---

    public function testRemoveBadge(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');
        $category->addBadge($badge);

        $result = $category->removeBadge($badge);

        $this->assertSame($category, $result, 'removeBadge should return $this for fluent interface');
        $this->assertCount(0, $category->getBadges(false));
        $this->assertNull($badge->getCategory());
    }

    public function testRemoveBadgeDoesNothingWhenAbsent(): void
    {
        $category = $this->createCategory('Test');
        $badge1 = $this->createBadge('Badge1');
        $badge2 = $this->createBadge('Badge2');
        $category->addBadge($badge1);

        $category->removeBadge($badge2);

        $this->assertCount(1, $category->getBadges(false));
        $this->assertTrue($category->getBadges(false)->contains($badge1));
    }

    public function testRemoveBadgeFromEmpty(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');

        $category->removeBadge($badge);

        $this->assertCount(0, $category->getBadges(false));
    }

    public function testRemoveBadgeUnsetsCategory(): void
    {
        $category = $this->createCategory('Test');
        $badge = $this->createBadge('Badge');
        $category->addBadge($badge);

        $this->assertSame($category, $badge->getCategory());

        $category->removeBadge($badge);

        $this->assertNull($badge->getCategory());
    }

    public function testRemoveBadgeDoesNotUnsetCategoryIfAlreadyChanged(): void
    {
        $category1 = $this->createCategory('Cat1');
        $category2 = $this->createCategory('Cat2');
        $badge = $this->createBadge('Badge');

        $category1->addBadge($badge);

        // Reassign badge to a different category before removal
        $badge->setCategory($category2);
        $category1->removeBadge($badge);

        // Category should remain as category2 since it was already changed
        $this->assertSame($category2, $badge->getCategory());
    }

    // --- toSearchResults ---

    public function testToSearchResults(): void
    {
        $category = $this->createCategory('First Aid', 42);

        $result = $category->toSearchResults();

        $this->assertIsArray($result);
        $this->assertSame('42', $result['id']);
        $this->assertSame('First Aid', $result['name']);
    }

    public function testToSearchResultsEscapesHtml(): void
    {
        $category = $this->createCategory('<b>Bold</b>', 1);

        $result = $category->toSearchResults();

        $this->assertStringNotContainsString('<b>', $result['name']);
        $this->assertStringContainsString('&lt;b&gt;', $result['name']);
    }

    public function testToSearchResultsWithSpecialCharacters(): void
    {
        $category = $this->createCategory('Cat & "Dogs"', 5);

        $result = $category->toSearchResults();

        // The raw '&' and '"' should be escaped via htmlspecialchars
        $this->assertStringContainsString('&amp;', $result['name']);
        $this->assertStringContainsString('&quot;', $result['name']);
        // The unescaped form should not be present
        $this->assertStringNotContainsString('Cat & "', $result['name']);
    }

    public function testToSearchResultsNullId(): void
    {
        $category = $this->createCategory('Test');
        // id is null because it was not set via reflection

        $result = $category->toSearchResults();

        $this->assertSame('', $result['id']);
        $this->assertSame('Test', $result['name']);
    }
}
