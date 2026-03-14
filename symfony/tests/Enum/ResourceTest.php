<?php

namespace App\Tests\Enum;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Resource;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testCategoryValue(): void
    {
        $enum = Resource::CATEGORY();
        $this->assertSame(Category::class, $enum->getValue());
    }

    public function testBadgeValue(): void
    {
        $enum = Resource::BADGE();
        $this->assertSame(Badge::class, $enum->getValue());
    }

    public function testUserValue(): void
    {
        $enum = Resource::USER();
        $this->assertSame(User::class, $enum->getValue());
    }

    public function testStructureValue(): void
    {
        $enum = Resource::STRUCTURE();
        $this->assertSame(Structure::class, $enum->getValue());
    }

    public function testVolunteerValue(): void
    {
        $enum = Resource::VOLUNTEER();
        $this->assertSame(Volunteer::class, $enum->getValue());
    }

    // --- getManager ---

    public function testGetManagerForCategory(): void
    {
        $this->assertSame(CategoryManager::class, Resource::CATEGORY()->getManager());
    }

    public function testGetManagerForBadge(): void
    {
        $this->assertSame(BadgeManager::class, Resource::BADGE()->getManager());
    }

    public function testGetManagerForUser(): void
    {
        $this->assertSame(UserManager::class, Resource::USER()->getManager());
    }

    public function testGetManagerForStructure(): void
    {
        $this->assertSame(StructureManager::class, Resource::STRUCTURE()->getManager());
    }

    public function testGetManagerForVolunteer(): void
    {
        $this->assertSame(VolunteerManager::class, Resource::VOLUNTEER()->getManager());
    }

    // --- getVoter ---

    public function testGetVoterForCategory(): void
    {
        $this->assertSame('CATEGORY', Resource::CATEGORY()->getVoter());
    }

    public function testGetVoterForBadge(): void
    {
        $this->assertSame('BADGE', Resource::BADGE()->getVoter());
    }

    public function testGetVoterForUser(): void
    {
        $this->assertSame('USER', Resource::USER()->getVoter());
    }

    public function testGetVoterForStructure(): void
    {
        $this->assertSame('STRUCTURE', Resource::STRUCTURE()->getVoter());
    }

    public function testGetVoterForVolunteer(): void
    {
        $this->assertSame('VOLUNTEER', Resource::VOLUNTEER()->getVoter());
    }

    // --- getProviderMethod ---

    public function testGetProviderMethodForUserReturnsFindOneByUsername(): void
    {
        $this->assertSame('findOneByUsername', Resource::USER()->getProviderMethod());
    }

    public function testGetProviderMethodForNonUserReturnsFindOneByExternalId(): void
    {
        $this->assertSame('findOneByExternalId', Resource::CATEGORY()->getProviderMethod());
        $this->assertSame('findOneByExternalId', Resource::BADGE()->getProviderMethod());
        $this->assertSame('findOneByExternalId', Resource::STRUCTURE()->getProviderMethod());
        $this->assertSame('findOneByExternalId', Resource::VOLUNTEER()->getProviderMethod());
    }

    // --- getPersisterMethod ---

    public function testGetPersisterMethodReturnsSave(): void
    {
        $this->assertSame('save', Resource::CATEGORY()->getPersisterMethod());
        $this->assertSame('save', Resource::BADGE()->getPersisterMethod());
        $this->assertSame('save', Resource::USER()->getPersisterMethod());
        $this->assertSame('save', Resource::STRUCTURE()->getPersisterMethod());
        $this->assertSame('save', Resource::VOLUNTEER()->getPersisterMethod());
    }

    // --- getDisplayName ---

    public function testGetDisplayNameReturnsLowercaseVoter(): void
    {
        $this->assertSame('category', Resource::CATEGORY()->getDisplayName());
        $this->assertSame('badge', Resource::BADGE()->getDisplayName());
        $this->assertSame('user', Resource::USER()->getDisplayName());
        $this->assertSame('structure', Resource::STRUCTURE()->getDisplayName());
        $this->assertSame('volunteer', Resource::VOLUNTEER()->getDisplayName());
    }

    public function testAllValuesExist(): void
    {
        $values = Resource::toArray();
        $this->assertCount(5, $values);
    }
}
