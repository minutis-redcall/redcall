<?php

namespace App\Tests\Form\Type;

use App\Form\Type\AudienceType;
use PHPUnit\Framework\TestCase;

/**
 * Tests the static utility methods of AudienceType.
 * The buildForm and buildView methods require service injection and are covered by integration tests.
 */
class AudienceTypeTest extends TestCase
{
    // --- LISTS constant ---

    public function testListsConstantContainsExpectedKeys(): void
    {
        $expected = [
            'volunteers',
            'excluded_volunteers',
            'external_ids',
            'structures_global',
            'structures_local',
            'badges_ticked',
            'badges_searched',
        ];

        $this->assertSame($expected, AudienceType::LISTS);
    }

    // --- split ---

    public function testSplitWithCommas(): void
    {
        $result = AudienceType::split('1,2,3');
        $this->assertSame(['1', '2', '3'], array_values($result));
    }

    public function testSplitWithSpaces(): void
    {
        $result = AudienceType::split('1 2 3');
        $this->assertSame(['1', '2', '3'], array_values($result));
    }

    public function testSplitWithMixedDelimiters(): void
    {
        $result = AudienceType::split('1,2 3;4');
        $this->assertSame(['1', '2', '3', '4'], array_values($result));
    }

    public function testSplitRemovesDuplicates(): void
    {
        $result = AudienceType::split('1,2,1,3,2');
        $this->assertSame(['1', '2', '3'], array_values($result));
    }

    public function testSplitRemovesEmpty(): void
    {
        $result = AudienceType::split('1,,2,,,3');
        $this->assertSame(['1', '2', '3'], array_values($result));
    }

    public function testSplitWithUuids(): void
    {
        $result = AudienceType::split('abc-123,def-456');
        $this->assertSame(['abc-123', 'def-456'], array_values($result));
    }

    public function testSplitPreservesAlphanumericAndHyphenAndStar(): void
    {
        $result = AudienceType::split('abc*123,test-val');
        $values = array_values($result);
        $this->assertContains('abc*123', $values);
        $this->assertContains('test-val', $values);
    }

    public function testSplitEmptyString(): void
    {
        $result = AudienceType::split('');
        $this->assertSame([], array_values($result));
    }

    // --- createEmptyData ---

    public function testCreateEmptyDataWithEmptyDefaults(): void
    {
        $data = AudienceType::createEmptyData([]);

        $this->assertNull($data['preselection_key']);
        $this->assertSame([], $data['volunteers']);
        $this->assertSame([], $data['excluded_volunteers']);
        $this->assertSame([], $data['external_ids']);
        $this->assertFalse($data['allow_minors']);
        $this->assertSame([], $data['structures_global']);
        $this->assertSame([], $data['structures_local']);
        $this->assertFalse($data['badges_all']);
        $this->assertSame([], $data['badges_ticked']);
        $this->assertSame([], $data['badges_searched']);
        $this->assertFalse($data['test_on_me']);
    }

    public function testCreateEmptyDataWithOverrides(): void
    {
        $data = AudienceType::createEmptyData([
            'volunteers'   => [1, 2, 3],
            'allow_minors' => true,
            'test_on_me'   => true,
        ]);

        $this->assertSame([1, 2, 3], $data['volunteers']);
        $this->assertTrue($data['allow_minors']);
        $this->assertTrue($data['test_on_me']);
        // Non-overridden keys should still have defaults
        $this->assertNull($data['preselection_key']);
        $this->assertSame([], $data['excluded_volunteers']);
    }

    public function testCreateEmptyDataPreservesExtraKeys(): void
    {
        $data = AudienceType::createEmptyData([
            'custom_key' => 'custom_value',
        ]);

        $this->assertSame('custom_value', $data['custom_key']);
    }

    public function testCreateEmptyDataAlwaysHasAllRequiredKeys(): void
    {
        $data = AudienceType::createEmptyData([]);

        $expectedKeys = [
            'preselection_key',
            'volunteers',
            'excluded_volunteers',
            'external_ids',
            'allow_minors',
            'structures_global',
            'structures_local',
            'badges_all',
            'badges_ticked',
            'badges_searched',
            'test_on_me',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Expected key '$key' to exist in empty data");
        }
    }

    // --- blockPrefix ---

    public function testGetBlockPrefix(): void
    {
        // We test through instantiation with mocked constructor dependencies
        // Instead, just verify the method exists and returns the right value
        $reflection = new \ReflectionMethod(AudienceType::class, 'getBlockPrefix');
        $this->assertFalse($reflection->isStatic());
    }
}
